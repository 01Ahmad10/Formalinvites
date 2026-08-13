<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('activity_type')->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('address')->nullable();
            $table->string('location_url', 2048)->nullable();
            $table->text('location_notes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['event_id', 'is_active', 'display_order', 'starts_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('event_activities'); }
};
