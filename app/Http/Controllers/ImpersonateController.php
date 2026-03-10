<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonateController extends Controller
{
    public function leave(ImpersonateManager $manager): RedirectResponse
    {
        if (! $manager->isImpersonating()) {
            abort(403);
        }

        $fromFilament = session()->pull('impersonated_from_filament', false);

        $manager->leave();

        if ($fromFilament) {
            return redirect('/admin/impersonate-user');
        }

        $leaveRedirect = $manager->getLeaveRedirectTo();

        if ($leaveRedirect !== 'back') {
            return redirect()->to($leaveRedirect);
        }

        return redirect()->back();
    }
}
