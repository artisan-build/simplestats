<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pulse_entries', function (Blueprint $table): void {
            $table->id();
            $table->integer('timestamp');
            $table->string('type');
            $table->text('key');
            $table->uuid('key_hash');
            $table->bigInteger('value')->nullable();

            $table->index('key_hash');
            $table->index('timestamp');
            $table->index(['timestamp', 'type', 'key_hash', 'value']);
            $table->index('type');
        });
    }
};
