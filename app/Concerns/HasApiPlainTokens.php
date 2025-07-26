<?php

namespace App\Concerns;

use DateTimeInterface;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

trait HasApiPlainTokens
{
    use HasApiTokens;

    public function createTokenPlain(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $token = $this->createToken(...func_get_args());

        $token->accessToken->token_plain = $token->plainTextToken;
        $token->accessToken->save();

        return $token;
    }
}
