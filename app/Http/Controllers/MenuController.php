<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function toggle(Request $request)
    {
        $showMenu = $request->user()->settings['showMenu'] ?? true;
        $request->user()->updateSettings('showMenu', ! $showMenu);

        return redirect()->back();
    }
}
