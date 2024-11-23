<?php

namespace App\Models;

use Carbon\CarbonInterface;
use \Laravel\Paddle\Transaction as PaddleTransaction;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePaymentWithCondition;

class Transaction extends PaddleTransaction implements TrackablePaymentWithCondition
{
    public function passTrackingCondition(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function watchTrackingFields(): array
    {
        return ['status'];
    }

    public function getTrackingPerson(): TrackablePerson
    {
        return $this->billable;
    }

    public function getTrackingTime(): CarbonInterface
    {
        return $this->{self::CREATED_AT};
    }

    public function getTrackingGross(): float
    {
        return $this->total;
    }

    public function getTrackingNet(): float
    {
        return $this->total - $this->tax;
    }

    public function getTrackingCurrency(): string
    {
        return $this->currency;
    }
}
