<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function toggleMenu(Request $request)
    {
        $showMenu = $request->user()->settings['showMenu'] ?? true;
        $request->user()->updateSettings('showMenu', ! $showMenu);

        return redirect()->back();
    }

    public function toggleAdvanced(Request $request)
    {
        $showAdvanced = $request->user()->settings['showAdvanced'] ?? false;
        $request->user()->updateSettings('showAdvanced', ! $showAdvanced);

        return redirect()->back();
    }
}
