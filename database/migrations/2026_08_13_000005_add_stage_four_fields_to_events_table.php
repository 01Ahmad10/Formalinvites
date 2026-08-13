<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_timezone')->default(config('app.timezone'));
            $table->text('dress_code')->nullable();
            $table->text('parking_information')->nullable();
            $table->text('transportation_information')->nullable();
            $table->text('accommodation_information')->nullable();
            $table->text('guest_information')->nullable();
        });

        DB::table('events')->whereNull('event_timezone')->update(['event_timezone' => config('app.timezone')]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['event_timezone', 'dress_code', 'parking_information', 'transportation_information', 'accommodation_information', 'guest_information']);
        });
    }
};
