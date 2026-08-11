<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });

        DB::table('payments')->where('paid_amount', '>', 0)->orderBy('id')->each(function (object $payment): void {
            DB::table('payment_transactions')->insert([
                'payment_id' => $payment->id,
                'amount' => $payment->paid_amount,
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
                'payment_date' => $payment->payment_date,
                'notes' => $payment->notes,
                'status' => 'confirmed',
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
