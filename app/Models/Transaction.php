<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Auth\User;
use \Laravel\Paddle\Transaction as PaddleTransaction;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePayment;

class Transaction extends PaddleTransaction implements TrackablePayment
{
    public function getTrackingUser(): User
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
        return $this->total - $this->tax - (0.05 * $this->total + 0.50);
    }

    public function getTrackingCurrency(): string
    {
        return $this->currency;
    }
}
