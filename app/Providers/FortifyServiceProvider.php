<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Users marked for deletion (soft-deleted) keep their data during the grace
        // window but must not be able to log back in; an admin restores them via
        // app:restore-user. withTrashed() so the block does not silently depend on
        // the SoftDeletes global scope being applied here.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::withTrashed()
                ->where(Fortify::username(), $request->input(Fortify::username()))
                ->first();

            if ($user && ! $user->trashed() && Hash::check($request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Fortify::registerView(function (Request $request) {
            if ($request->has('invitation_mail')) {
                session()->put('invitation', [
                    'invitationMail' => $request->invitation_mail ?? '',
                    'acceptInvitationLink' => $request->accept_invitation_link ?? '',
                    'teamName' => urldecode($request->team_name) ?? '',
                    'teamOwner' => urldecode($request->team_owner) ?? '',
                ]);
            }

            return Inertia::render('Auth/Register', [
                'params' => session('invitation') ?? [],
            ]);
        });

        Fortify::loginView(function () {
            $data = [
                'canResetPassword' => Route::has('password.request'),
                'status' => session('status'),
            ];

            if (! app()->isProduction()) {
                $data['testUser'] = config('app.test.user');
            }

            return Inertia::render('Auth/Login', $data);
        });
    }
}
