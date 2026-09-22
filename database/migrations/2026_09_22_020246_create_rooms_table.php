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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date');
            $table->string('invite_code', 6)->unique();
            $table->tinyInteger('team_size');
            $table->tinyInteger('num_teams');
            $table->unsignedBigInteger('price_in_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('waiting');
            $table->json('draft_order')->nullable();
            $table->integer('current_pick_index')->default(0);
            $table->timestamp('draft_started_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
