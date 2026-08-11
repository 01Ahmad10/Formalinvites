<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('events', function (Blueprint $table) { $table->id(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('event_package_id')->nullable()->constrained()->nullOnDelete(); $table->string('title'); $table->string('event_type'); $table->string('host_name'); $table->string('second_host_name')->nullable(); $table->text('description')->nullable(); $table->date('main_date')->nullable(); $table->time('start_time')->nullable(); $table->time('end_time')->nullable(); $table->string('venue')->nullable(); $table->string('address')->nullable(); $table->string('location_url')->nullable(); $table->date('rsvp_deadline')->nullable(); $table->string('status')->default('draft'); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('events'); }
};
