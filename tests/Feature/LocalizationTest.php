<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(fn () => $this->skipUnlessSaas());

// SetLocale middleware tests

test('SetLocale middleware sets locale for supported non-default locale', function (): void {
    $middleware = new SetLocale;
    $request = Request::create('/de');

    $middleware->handle($request, fn (): Response => new Response);

    expect(app()->getLocale())->toBe('de');
});

test('SetLocale middleware keeps default locale when no prefix', function (): void {
    $middleware = new SetLocale;
    $request = Request::create('/');

    $middleware->handle($request, fn (): Response => new Response);

    expect(app()->getLocale())->toBe('en');
});

test('SetLocale middleware keeps default locale for unsupported locale', function (): void {
    $middleware = new SetLocale;
    $request = Request::create('/xx/login');

    $middleware->handle($request, fn (): Response => new Response);

    expect(app()->getLocale())->toBe('en');
});

test('SetLocale middleware does not set locale for default locale prefix', function (): void {
    $middleware = new SetLocale;
    $request = Request::create('/en');

    $middleware->handle($request, fn (): Response => new Response);

    expect(app()->getLocale())->toBe('en');
});

// Localized route tests

test('localized landing page returns 200', function (string $locale): void {
    $this->get("/{$locale}")
        ->assertStatus(200);
})->with(['de', 'fr', 'it', 'es', 'nl']);

test('localized login page returns 200', function (string $locale): void {
    $this->get("/{$locale}/login")
        ->assertStatus(200);
})->with(['de', 'fr', 'it', 'es', 'nl']);

test('localized register page returns 200', function (string $locale): void {
    $this->get("/{$locale}/register")
        ->assertStatus(200);
})->with(['de', 'fr', 'it', 'es', 'nl']);

test('localized forgot-password page returns 200', function (string $locale): void {
    $this->get("/{$locale}/forgot-password")
        ->assertStatus(200);
})->with(['de', 'fr', 'it', 'es', 'nl']);

test('localized imprint page returns 200', function (string $locale): void {
    $this->get("/{$locale}/imprint")
        ->assertStatus(200);
})->with(['de', 'fr', 'it', 'es', 'nl']);

test('unsupported locale prefix returns 404', function (): void {
    $this->get('/xx')
        ->assertStatus(404);
});

test('localized dashboard route does not exist', function (): void {
    $this->get('/de/dashboard')
        ->assertStatus(404);
});

test('default English routes still work', function (): void {
    $this->get('/')->assertStatus(200);
    $this->get('/login')->assertStatus(200);
    $this->get('/register')->assertStatus(200);
    $this->get('/imprint')->assertStatus(200);
});

// hreflang tag tests

test('hreflang tags are present on localized pages', function (): void {
    $response = $this->get('/de');

    $response->assertStatus(200);
    $response->assertSee('hreflang="de"', false);
    $response->assertSee('hreflang="en"', false);
    $response->assertSee('hreflang="x-default"', false);
    $response->assertSee('hreflang="fr"', false);
});

test('hreflang tags are present on English landing page', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('hreflang="en"', false);
    $response->assertSee('hreflang="x-default"', false);
    $response->assertSee('hreflang="de"', false);
});

test('hreflang tags contain correct URLs for landing page', function (): void {
    $response = $this->get('/de');

    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/"', false);
    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/de"', false);
    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/fr"', false);
});

test('hreflang tags contain correct URLs for login page', function (): void {
    $response = $this->get('/de/login');

    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/login"', false);
    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/de/login"', false);
    $response->assertSee('href="'.rtrim(config('app.url'), '/').'/fr/login"', false);
});

// Inertia shared data tests

test('locale data is shared via Inertia', function (): void {
    $response = $this->get('/de');

    $response->assertInertia(fn ($page) => $page
        ->where('locale', 'de')
        ->has('supportedLocales')
        ->has('defaultLocale')
        ->has('localeUrls')
    );
});

test('English locale is default when no prefix', function (): void {
    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('locale', 'en')
        ->where('defaultLocale', 'en')
    );
});

// Translation command test

test('translate command runs in dry-run mode', function (): void {
    $this->artisan('app:translate --dry-run')
        ->assertSuccessful();
});

// Config test

test('locales config returns expected values', function (): void {
    expect(array_keys(config('locales.supported')))->toBe(['en', 'de', 'fr', 'it', 'es', 'nl']);
    expect(config('locales.default'))->toBe('en');
});
