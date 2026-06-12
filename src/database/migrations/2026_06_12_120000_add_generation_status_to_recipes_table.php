<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ticket 3.8 turns recipe generation into an async, queued flow:
     * - `generation_status` / `generation_error` track the job the frontend polls.
     * - `description` / `servings` are returned by the parser but were missing
     *   from the 3.1 schema.
     *
     * Additive only — the AI-populated columns (`name`, *_json) stay NOT NULL and
     * are seeded with placeholders (''/[]) on the pending row, then overwritten
     * when the job completes. This avoids fragile column-change rebuilds on the
     * sqlite test database.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('generation_status')->default('pending');
            $table->text('generation_error')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('servings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['generation_status', 'generation_error', 'description', 'servings']);
        });
    }
};
