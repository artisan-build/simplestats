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
        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('frequency')->nullable();
            $table->string('type')->default('default');
            $table->json('settings');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('user_id');
            $table->unique(['user_id', 'notifiable_type', 'notifiable_id', 'type'], 'user_notifications_combined_unique');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
