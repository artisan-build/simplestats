<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Paddle\Cashier;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Spark's Billable trait creates a Paddle customer on every user
        // insert, so without this every factory user hits the real Paddle
        // sandbox API (slow, flaky, and it rate-limits across test runs).
        // Spark is saas-only, so the self-hosted build has no Cashier.
        if (class_exists(Cashier::class)) {
            Cashier::fake();
        }
    }

    /**
     * CI runs the self-hosted build (composer.json), which lacks the saas-only
     * packages and code paths (Spark, Filament, blog, landing pages, ...).
     */
    public function skipUnlessSaas(): void
    {
        if (config('app.app_type') !== 'saas') {
            $this->markTestSkipped('Requires the saas build.');
        }
    }
}
