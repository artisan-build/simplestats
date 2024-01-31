<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEarlySubscriptionRequest;
use App\Models\EarlySubscriber;
use Illuminate\Http\Request;

class EarlySubscriptionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreEarlySubscriptionRequest $request)
    {
        EarlySubscriber::create($request->validated());

        return redirect()->back();
    }
}
