<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->casts['token_plain'] = 'encrypted';
    }
}
