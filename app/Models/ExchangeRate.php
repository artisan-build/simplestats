<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'rates',
    ];

    protected $casts = [
        'date' => 'date',
        'rates' => 'array',
    ];
}
