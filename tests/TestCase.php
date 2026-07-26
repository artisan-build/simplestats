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

        // Spark's Billable trait creates a Paddle customer on every user
        // insert, so without this every factory user hits the real Paddle
        // sandbox API (slow, flaky, and it rate-limits across test runs).
        Cashier::fake();
    }
}
