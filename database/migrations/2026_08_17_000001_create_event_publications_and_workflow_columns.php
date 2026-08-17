<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('submitted_snapshot_hash', 64)->nullable()->after('status');
            $table->timestampTz('submitted_at')->nullable()->after('submitted_snapshot_hash');
            $table->string('approved_snapshot_hash', 64)->nullable()->after('submitted_at');
            $table->timestampTz('approved_at')->nullable()->after('approved_snapshot_hash');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('approved_by');
            $table->index(['status', 'submitted_snapshot_hash']);
        });

        Schema::create('event_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('snapshot_hash', 64);
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('published_at');
            $table->timestamps();
            $table->unique(['event_id', 'version']);
            $table->index(['event_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_publications');
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'submitted_snapshot_hash']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['submitted_snapshot_hash', 'submitted_at', 'approved_snapshot_hash', 'approved_at', 'review_note']);
        });
    }
};
