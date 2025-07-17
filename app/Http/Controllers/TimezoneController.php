<?php

namespace App\Http\Controllers;

class TimezoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return collect(timezone_identifiers_list())->map(function ($timezone, $key) {
            return [
                'id' => $timezone,
                'name' => $timezone,
            ];
        });
    }
}
