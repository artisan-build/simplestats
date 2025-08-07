<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $casts = [
        'date' => 'date',
        'rates' => 'array',
    ];
}
