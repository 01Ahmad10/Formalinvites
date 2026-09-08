<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // One is a safe default for a newly provisioned Customer; the
            // backfill below raises existing Customers to their current usage.
            $table->unsignedInteger('allowed_events')->default(1)->after('is_active');
        });

        DB::table('customers')->orderBy('id')->each(function (object $customer): void {
            $usedEvents = DB::table('events')->where('customer_id', $customer->id)->count();

            if ($usedEvents > 1) {
                DB::table('customers')->where('id', $customer->id)->update(['allowed_events' => $usedEvents]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('allowed_events');
        });
    }
};
