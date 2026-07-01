<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Laravel\Paddle\Transaction as PaddleTransaction;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePaymentWithCondition;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Data\TrackingSubscription;

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

    // TODO: map the Paddle subscription (plan + interval) once recurring tracking
    // is wired up; null keeps the prior one-time-payment behaviour for now.
    public function getTrackingSubscription(): ?TrackingSubscription
    {
        return null;
    }
}
