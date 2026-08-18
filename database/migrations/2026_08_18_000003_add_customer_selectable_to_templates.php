<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) { $table->boolean('is_customer_selectable')->default(false)->after('is_active'); });
        DB::table('templates')->whereIn('component_key', ['romantic-floral', 'editorial-luxury', 'modern-cinematic'])->update(['is_customer_selectable' => true]);
    }
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) { $table->dropColumn('is_customer_selectable'); });
    }
};
