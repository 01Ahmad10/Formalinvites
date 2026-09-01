<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['main_date', 'id']);
            $table->index(['customer_id', 'created_at']);
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index(['status', 'payment_date']);
            $table->index(['status', 'payment_id']);
        });

        Schema::table('rsvps', function (Blueprint $table) {
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('rsvps', fn (Blueprint $table) => $table->dropIndex(['status', 'submitted_at']));
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_date']);
            $table->dropIndex(['status', 'payment_id']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['main_date', 'id']);
            $table->dropIndex(['customer_id', 'created_at']);
        });
    }
};
