<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('discount');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->foreignId('coupon_id')->nullable()->after('event_package_id')->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
        });

        DB::table('payments')->orderBy('id')->each(function (object $payment): void {
            $finalAmount = max((float) $payment->final_amount, 0);
            $paidAmount = (float) $payment->paid_amount;
            $status = $paidAmount >= $finalAmount ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : 'unpaid');

            DB::table('payments')->where('id', $payment->id)->update([
                'final_amount' => $finalAmount,
                'discount_type' => (float) $payment->discount > 0 ? 'fixed' : null,
                'discount_value' => (float) $payment->discount > 0 ? $payment->discount : null,
                'status' => $status,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_value', 'discount_type']);
        });
    }
};
