<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('ingredients_json');
            $table->json('steps_json');
            $table->json('kbju_json');
            $table->json('pantry_snapshot_json');
            $table->json('selected_family_members_json');
            $table->enum('status', ['generated', 'cooked'])->default('generated');
            $table->boolean('is_favorite')->default(false);
            $table->timestamp('cooked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
