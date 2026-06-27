<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Laravel\Paddle\Transaction as PaddleTransaction;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePaymentWithCondition;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Data\TrackingSubscription;
use SimpleStatsIo\LaravelClient\Enums\SubscriptionInterval;

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

    public function getTrackingSubscription(): ?TrackingSubscription
    {
        $subscription = $this->subscription;

        if (! $subscription) {
            return null;
        }

        $subscriptionService = app(SubscriptionService::class);

        foreach ($subscription->items as $item) {
            $interval = $subscriptionService->intervalForPriceId($item->price_id);

            if ($interval) {
                return new TrackingSubscription(
                    plan: $subscriptionService->planForPriceId($item->price_id),
                    interval: SubscriptionInterval::from($interval->value),
                );
            }
        }

        return null;
    }
}
