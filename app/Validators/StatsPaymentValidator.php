<?php

namespace App\Validators;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Validator;
use Spatie\ValidationRules\Rules\Currency;

class StatsPaymentValidator extends BaseValidator
{
    public function prepareForValidation(array $input): array
    {
        // if the currency is not valid, we do not check if we have to convert it...
        // it will fail anyway in the request validation...
        if (Validator::make(['currency' => $input['currency']], ['currency' => new Currency()])->fails()) {
            return [];
        }

        // check if we have to convert currencies
        $teamCurrency = auth()->user()->team->currency;
        if ($input['currency'] != $teamCurrency) {
            $exchangeRate = app(ExchangeRateService::class)->getFor(now(), $input['currency'], auth()->user()->team->currency);
            $input = array_merge($input, [
                'orig_gross' => $input['gross'],
                'orig_net' => $input['net'],
                'orig_currency' => $input['currency'],
                'gross' => $input['gross'] * $exchangeRate,
                'net' => $input['net'] * $exchangeRate,
                'currency' => $teamCurrency,
            ]);
        }

        if (isset($input['gross']))        $input['gross'] = round($input['gross'], 2);
        if (isset($input['orig_gross']))   $input['orig_gross'] = round($input['orig_gross'], 2);

        if (isset($input['net']))          $input['net'] = round($input['net'], 2);
        if (isset($input['orig_net']))     $input['orig_net'] = round($input['orig_net'], 2);

        return $input;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'stats_user_id' => ['required', 'integer', 'exists:stats_users,id'],
            'gross' => ['required', 'numeric'],
            'net' => ['required', 'numeric'],
            'currency' => ['required', new Currency()],
            'orig_gross' => ['sometimes', 'numeric'],
            'orig_net' => ['sometimes', 'numeric'],
            'orig_currency' => ['sometimes', new Currency()],
            'time' => ['required', 'date'],
        ];
    }
}
