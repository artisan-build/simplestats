<?php

namespace App\Http\Controllers;

use App\Http\DataProviders\LoginSummaryDataProvider;
use Illuminate\Http\JsonResponse;

class LoginSummaryController extends Controller
{
    public function __invoke(LoginSummaryDataProvider $dataProvider): JsonResponse
    {
        return response()->json($dataProvider->toArray());
    }
}
