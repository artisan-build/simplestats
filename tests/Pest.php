<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// Arch tests load app classes (e.g. App\Models\User reads config at load time).
uses(TestCase::class)->in('ArchTest.php');

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function (): void {
        // All stats ingest API endpoints use defer() to dispatch jobs after
        // the HTTP response. Without this, deferred callbacks never run
        // before test assertions, causing false failures.
        $this->withoutDefer();

        // Authenticated guards and Context leak across test boundaries within
        // the same process: an actingAs() from a previous test bleeds into the
        // next test's API requests, where SetProjectContext then records the
        // USER id as project_id and BaseStatsTrack's findOrFail 404s. Reset both.
        auth()->forgetGuards();
        Context::flush();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function apiHeaders(string $token): array
{
    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}
