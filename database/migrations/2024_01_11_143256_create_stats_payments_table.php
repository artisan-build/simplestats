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
        Schema::create('stats_payments', function (Blueprint $table) {
            /**
             * We can't make a primary key "id" here, cause as this is a SaaS where many instances
             * will send their payments, a stats_payment_id will only be unique on a stats_payment/instance basis...
             */
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('stats_user_id')->index();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instance_id')->constrained()->cascadeOnDelete();
            $table->float('gross');
            $table->float('net');
            $table->string('currency', 3);
            $table->float('orig_gross')->nullable();
            $table->float('orig_net')->nullable();
            $table->string('orig_currency', 3)->nullable();
            $table->date('time')->index();
            $table->integer('counter')->index();

            $table->primary(['id', 'instance_id']);

            $table->foreign(['stats_user_id', 'instance_id'])
                ->references(['id', 'instance_id'])
                ->on('stats_users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_payments');
    }
};
