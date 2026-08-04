<?php

namespace App\Validators;

use App\Enums\SubscriptionInterval;
use App\Services\ExchangeRateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\ValidationRules\Rules\Currency;

class StatsPaymentValidator extends BaseStatsValidator
{
    public function prepareForValidation(array $input): array
    {
        $input = parent::prepareForValidation($input);

        if (! isset($input['currency'])) {
            return $input;
        }

        $team = auth()->user()->team;
        // if the currency is not valid, we do not check if we have to convert it...
        // it will fail anyway in the request validation...
        if (Validator::make(
            ['currency' => $input['currency']],
            ['currency' => new Currency]
        )->fails()) {
            return $input;
        }

        if (! ($team->billingOwner?->sparkPlan()->options['MultiCurrency'] ?? false)) {
            Validator::make(
                ['currency' => $input['currency']],
                ['currency' => 'in:'.$team->currency]
            )->validate();
        }

        // these fields need to exist for the batch import upsert
        if (! isset($input['orig_gross'])) {
            $input['orig_gross'] = null;
        }
        if (! isset($input['orig_net'])) {
            $input['orig_net'] = null;
        }
        if (! isset($input['orig_currency'])) {
            $input['orig_currency'] = null;
        }
        if (! isset($input['subscription_interval'])) {
            $input['subscription_interval'] = null;
        }
        if (! isset($input['subscription_plan'])) {
            $input['subscription_plan'] = null;
        }

        // check if we have to convert currencies
        $teamCurrency = $team->currency;
        if ($input['currency'] != $teamCurrency) {
            $exchangeRate = app(ExchangeRateService::class)->getFor(CarbonImmutable::parse($input['time']), $input['currency'], $team->currency);
            $input = array_merge($input, [
                'orig_gross' => $input['gross'],
                'orig_net' => $input['net'],
                'orig_currency' => $input['currency'],
                'gross' => $input['gross'] * $exchangeRate,
                'net' => $input['net'] * $exchangeRate,
                'currency' => $teamCurrency,
            ]);
        }

        if (isset($input['gross'])) {
            $input['gross'] = round($input['gross'], 2);
        }
        if (isset($input['orig_gross'])) {
            $input['orig_gross'] = round($input['orig_gross'], 2);
        }

        if (isset($input['net'])) {
            $input['net'] = round($input['net'], 2);
        }
        if (isset($input['orig_net'])) {
            $input['orig_net'] = round($input['orig_net'], 2);
        }

        return $input;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'id' => ['required'],
            'stats_user_id' => ['nullable'],
            'visitor_hash' => ['nullable'],
            'gross' => ['required', 'numeric'],
            'net' => ['required', 'numeric'],
            'currency' => ['required', new Currency],
            'orig_gross' => ['sometimes', 'nullable', 'numeric'],
            'orig_net' => ['sometimes', 'nullable', 'numeric'],
            'orig_currency' => ['sometimes', 'nullable', new Currency],
            'subscription_interval' => ['sometimes', 'nullable', Rule::enum(SubscriptionInterval::class)],
            'subscription_plan' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
    }
}
