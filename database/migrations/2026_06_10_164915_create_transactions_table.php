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
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('billable_id');
            $table->string('billable_type');
            $table->string('paddle_id');
            $table->string('paddle_subscription_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('status');
            $table->string('total');
            $table->string('tax');
            $table->string('currency', 3);
            $table->timestamp('billed_at');
            $table->timestamps();

            $table->index(['billable_id', 'billable_type']);
            $table->index('paddle_subscription_id');
            $table->unique('paddle_id');
        });
    }
};
