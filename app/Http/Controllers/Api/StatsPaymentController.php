<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsPaymentRequest;
use App\Http\Resources\StatsPaymentResource;
use App\Models\StatsPayment;

class StatsPaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsPayment::class, 'stats_payment');
    }

    public function store(StatsPaymentRequest $request): StatsPaymentResource
    {
        $statsPayment = StatsPayment::updateOrCreate(['id' => $request->id], $request->validated());

        return new StatsPaymentResource($statsPayment);
    }
}
