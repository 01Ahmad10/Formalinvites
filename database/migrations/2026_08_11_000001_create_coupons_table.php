<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('discount_type');
            $table->decimal('discount_value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
