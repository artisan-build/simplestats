<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links a registration back to the visitor it converted from, so Time-to-Convert
     * (visit -> registration) is computable. Forward-only: existing rows stay null
     * (the visitor->user link was never captured historically), the metric fills
     * going forward. Unlike stats_payments.stats_visitor_id this uses nullOnDelete,
     * not cascade: a removed visitor row must never delete the registered user.
     */
    public function up(): void
    {
        Schema::table('stats_users', function (Blueprint $table) {
            $table->unsignedBigInteger('stats_visitor_id')->nullable()->after('id');

            $table->index('stats_visitor_id');

            $table->foreign('stats_visitor_id')
                ->references('id')
                ->on('stats_visitors')
                ->nullOnDelete();
        });
    }
};
