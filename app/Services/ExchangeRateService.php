<?php

namespace App\Services;

use App\Models\ExchangeRate;
use AshAllenDesign\LaravelExchangeRates\Facades\ExchangeRate as ExchangeRateFacade;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Facades\Validator;
use Spatie\ValidationRules\Rules\Currency;

class ExchangeRateService
{
    /**
     * @throws Exception
     */
    public function getFor(CarbonInterface $date, string $fromCurrency, string $toCurrency): float|int
    {
        $validator = Validator::make(
            ['fromCurrency' => $fromCurrency, 'toCurrency' => $toCurrency],
            ['fromCurrency' => new Currency(), 'toCurrency' => new Currency()]
        );

        if ($validator->fails()) {
            throw new Exception('Invalid currencies supplied: '.implode(', ', $validator->errors()->all()));
        }

        $rateKey = "$fromCurrency-$toCurrency";

        $dateRate = ExchangeRate::where('date', $date->format('Y-m-d'))->first();

        if (! $dateRate) {
            $dateRate = ExchangeRate::create([
                'date' => $date,
                'rates' => [
                    // curl request (we want to save this if possible for performance and api calls limits...)
                    $rateKey => ExchangeRateFacade::exchangeRate($fromCurrency, $toCurrency),
                ],
            ]);
        }

        // wenn $dateRate existiert, aber der gesuchte kurs noch nicht...
        if (! isset($dateRate->rates[$rateKey])) {
            $rates = $dateRate->rates;
            $rates[$rateKey] = ExchangeRateFacade::exchangeRate($fromCurrency, $toCurrency);
            $dateRate->rates = $rates;
            $dateRate->save();
        }

        if ($dateRate->rates[$rateKey]) {
            return $dateRate->rates[$rateKey];
        } else {
            throw new Exception("can't find exchange rate conversion for $fromCurrency to $toCurrency");
        }
    }
}
