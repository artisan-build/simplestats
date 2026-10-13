<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->string('ai_provider')->nullable();
            $table->string('ai_model')->nullable();
            $table->text('ai_api_key')->nullable();
        });
    }
};
