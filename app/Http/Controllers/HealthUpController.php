<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class HealthUpController extends Controller
{
    public function __invoke(): Response
    {
        return response('OK');
    }
}
