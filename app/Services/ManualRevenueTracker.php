<?php

namespace App\Services;

use App\Jobs\TrackStatsPayment;
use App\Jobs\TrackStatsUser;
use App\Models\Project;
use App\Validators\StatsPaymentValidator;
use App\Validators\StatsUserValidator;
use Illuminate\Support\Facades\Context;

class ManualRevenueTracker
{
    /**
     * Persist a manually recorded stats user plus stats payment for the given
     * project, mirroring the API endpoints /stats-user and /stats-payment.
     *
     * The project is temporarily set as the authenticated "user" and the team /
     * project context is forced, exactly like an API ingest request, so the
     * validators (currency conversion against the team currency) and the model
     * saving hooks (team_id / project_id) resolve against the target project
     * rather than the acting admin.
     *
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $paymentData
     */
    public function track(Project $project, array $userData, array $paymentData): void
    {
        $originalUser = auth()->user();

        // Context must be set before auth so HasTeamScope/HasProjectScope and
        // ensureTeamTimezone resolve against the target project (see CLAUDE.md).
        Context::add('team_id', $project->team_id);
        Context::add('project_id', $project->id);
        auth()->setUser($project);

        try {
            $userInput = app(StatsUserValidator::class)->validate($userData);
            $paymentInput = app(StatsPaymentValidator::class)->validate($paymentData);

            // Run synchronously and in order so the stats user exists before the
            // payment that references it is persisted.
            TrackStatsUser::dispatchSync($userInput);
            TrackStatsPayment::dispatchSync($paymentInput);
        } finally {
            Context::forget('team_id');
            Context::forget('project_id');
            auth()->setUser($originalUser);
        }
    }
}
