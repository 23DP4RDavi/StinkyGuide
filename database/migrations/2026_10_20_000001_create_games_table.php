<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('genre');
            $table->unsignedInteger('steam_app_id')->unique();
            $table->json('tags');
            $table->string('platform');
            $table->unsignedTinyInteger('match_score')->default(75);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
