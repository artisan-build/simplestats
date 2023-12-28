<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->casts['token_plain'] = 'encrypted';
    }

    protected function tokenPlainPart(): Attribute
    {
        return Attribute::make(
            get: fn () => explode('|', $this->token_plain, 2)[1],
        );
    }
}
