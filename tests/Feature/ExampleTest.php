<?php

beforeEach(fn () => $this->skipUnlessSaas());

it('returns a successful response', function (): void {
    $response = $this->get('/');

    $response->assertStatus(200);
});
