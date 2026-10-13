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
        Schema::create('telescope_entries', function (Blueprint $table): void {
            $table->bigInteger('sequence');
            $table->uuid('uuid');
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->text('content');
            $table->timestamps();

            $table->index('batch_id');
            $table->index('created_at');
            $table->index('family_hash');
            $table->index(['type', 'should_display_on_index']);
            $table->unique('uuid');
        });
    }
};
