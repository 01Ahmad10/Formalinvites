<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->string('event_type')->nullable()->change();
            $table->string('host_name')->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_account_role', 20)->nullable()->after('role');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('customer_account_role'); });
    }
};
