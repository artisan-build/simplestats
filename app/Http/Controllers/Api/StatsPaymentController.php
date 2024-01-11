<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsPaymentRequest;
use App\Http\Requests\StatsPaymentUpdateRequest;
use App\Http\Resources\StatsPaymentResource;
use App\Models\StatsPayment;

class StatsPaymentController extends Controller
{
//    public function index(Request $request): StatsPaymentCollection
//    {
//        $statsPayments = StatsPayment::all();
//
//        return new StatsPaymentCollection($statsPayments);
//    }

    public function store(StatsPaymentRequest $request): StatsPaymentResource
    {
        $statsPayment = StatsPayment::create($request->validated());

        return new StatsPaymentResource($statsPayment);
    }

//    public function show(Request $request, StatsPayment $statsPayment): StatsPaymentResource
//    {
//        return new StatsPaymentResource($statsPayment);
//    }
//
//    public function update(StatsRequest $request, StatsPayment $statsPayment): StatsPaymentResource
//    {
//        $statsPayment->update($request->validated());
//
//        return new StatsPaymentResource($statsPayment);
//    }
//
//    public function destroy(Request $request, StatsPayment $statsPayment): Response
//    {
//        $statsPayment->delete();
//
//        return response()->noContent();
//    }
}
