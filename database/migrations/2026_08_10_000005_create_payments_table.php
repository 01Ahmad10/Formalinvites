<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('payments', function (Blueprint $table) { $table->id(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('event_package_id')->nullable()->constrained()->nullOnDelete(); $table->decimal('original_amount', 10, 2); $table->decimal('discount', 10, 2)->default(0); $table->decimal('final_amount', 10, 2); $table->decimal('paid_amount', 10, 2)->default(0); $table->string('payment_method')->nullable(); $table->string('reference')->nullable(); $table->date('payment_date')->nullable(); $table->string('status')->default('pending'); $table->text('notes')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('payments'); }
};
