<?php

namespace App\Http\Requests\Api;

use App\Services\ExchangeRateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Spatie\ValidationRules\Rules\Currency;

class StatsPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // if the currency is not valid, we do not check if we have to convert it...
        // it will fail anyway in the request validation...
        if (Validator::make(['currency' => $this->currency], ['currency' => new Currency()])->fails()) {
            return;
        }

        // check if we have to convert currencies
        $teamCurrency = $this->user()->team->currency;
        if ($this->currency != $teamCurrency) {
            $exchangeRate = app(ExchangeRateService::class)->getFor(now(), $this->currency, $this->user()->team->currency);
            $this->merge([
                'orig_gross' => $this->gross,
                'orig_net' => $this->net,
                'orig_currency' => $this->currency,
                'gross' => $this->gross * $exchangeRate,
                'net' => $this->net * $exchangeRate,
                'currency' => $teamCurrency,
            ]);
        }

        if (isset($this->gross))        $this->gross = round($this->gross, 2);
        if (isset($this->orig_gross))   $this->orig_gross = round($this->orig_gross, 2);

        if (isset($this->net))          $this->net = round($this->net, 2);
        if (isset($this->orig_net))     $this->orig_net = round($this->orig_net, 2);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
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
