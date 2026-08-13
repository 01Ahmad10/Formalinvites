<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('template_id')->nullable()->after('event_package_id')->constrained('templates')->nullOnDelete();
        });

        Schema::create('event_template_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_template_settings');
        Schema::table('events', function (Blueprint $table) { $table->dropConstrainedForeignId('template_id'); });
    }
};
