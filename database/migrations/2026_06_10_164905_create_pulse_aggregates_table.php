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
        Schema::create('pulse_aggregates', function (Blueprint $table) {
            $table->id();
            $table->integer('bucket');
            $table->integer('period');
            $table->string('type');
            $table->text('key');
            $table->uuid('key_hash');
            $table->string('aggregate');
            $table->decimal('value', 20, 2);
            $table->integer('count')->nullable();

            $table->index(['period', 'bucket']);
            $table->index(['period', 'type', 'aggregate', 'bucket']);
            $table->index('type');
            $table->unique(['bucket', 'period', 'type', 'aggregate', 'key_hash']);
        });
    }
};
