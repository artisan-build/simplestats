<?php

use App\Analytics\VisitorCustomPropertiesResolver;
use Illuminate\Http\Request;

beforeEach(fn () => $this->skipUnlessSaas());

const ACTIVE_HERO_VARIANTS = ['control', 'visitor_to_revenue', 'outcome_rotation'];

test('production landing assigns a hero variant, shares and persists it', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/');

    $response->assertCookie('hero_variant');
    $response->assertInertia(fn ($page) => $page
        ->where('heroVariant', fn ($v) => in_array($v, ACTIVE_HERO_VARIANTS, true))
    );
});

test('in production an existing variant cookie sticks', function () {
    $this->app['env'] = 'production';

    $response = $this->withUnencryptedCookie('hero_variant', 'outcome_rotation')->get('/');

    $response->assertInertia(fn ($page) => $page->where('heroVariant', 'outcome_rotation'));
});

test('in production an unknown variant cookie is reassigned to an active one', function () {
    $this->app['env'] = 'production';

    $response = $this->withUnencryptedCookie('hero_variant', 'does_not_exist')->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('heroVariant', fn ($v) => in_array($v, ACTIVE_HERO_VARIANTS, true) && $v !== 'does_not_exist')
    );
});

test('outside production the variant is not persisted and re-randomizes', function () {
    // The testing environment is non-production, so the cookie is ignored and never set.
    $response = $this->withUnencryptedCookie('hero_variant', 'control')->get('/');

    $response->assertCookieMissing('hero_variant');
    $response->assertInertia(fn ($page) => $page
        ->where('heroVariant', fn ($v) => in_array($v, ACTIVE_HERO_VARIANTS, true))
    );
});

test('the visitor resolver tags the hero variant from the cookie', function () {
    $request = Request::create('/', 'GET');
    $request->cookies->set('hero_variant', 'control');

    expect((new VisitorCustomPropertiesResolver)->resolve($request))->toBe(['hero_variant' => 'control']);
});

test('the visitor resolver returns nothing without a cookie', function () {
    expect((new VisitorCustomPropertiesResolver)->resolve(Request::create('/', 'GET')))->toBe([]);
});
