<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_invitation_contents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_locale', 12)->default('en');
            $table->boolean('story_enabled')->default(false);
            $table->string('story_heading')->nullable();
            $table->text('story_body')->nullable();
            $table->boolean('gift_registry_enabled')->default(false);
            $table->text('gift_registry_intro')->nullable();
            $table->boolean('ending_enabled')->default(false);
            $table->string('ending_title')->nullable();
            $table->text('ending_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invitation_contents');
    }
};
