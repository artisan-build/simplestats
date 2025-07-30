<?php

use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('notifiable');
            $table->string('frequency')->nullable();
            $table->string('type')->default(UserNotificationType::DEFAULT->value);
            $table->json('settings');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('user_id');

            $table->unique(['user_id', 'notifiable_type', 'notifiable_id', 'type']);
        });
    }
};
