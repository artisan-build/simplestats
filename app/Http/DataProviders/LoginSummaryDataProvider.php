<?php

namespace App\Http\DataProviders;

use App\Services\LoginSummaryService;
use Illuminate\Http\Request;

class LoginSummaryDataProvider extends AbstractDataProvider
{
    public function __construct(
        private Request $request,
        private LoginSummaryService $loginSummaryService,
    ) {}

    public function toArray(): array
    {
        $user = $this->request->user();

        if (! $this->loginSummaryService->isEligible($user)) {
            return ['summary' => null];
        }

        $this->loginSummaryService->consumePending();

        return ['summary' => $this->loginSummaryService->getSummary($user)];
    }
}
