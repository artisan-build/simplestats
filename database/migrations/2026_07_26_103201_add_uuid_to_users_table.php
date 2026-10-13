<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('id');
        });

        DB::table('users')->whereNull('uuid')->update(['uuid' => DB::raw('gen_random_uuid()')]);

        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }
};
