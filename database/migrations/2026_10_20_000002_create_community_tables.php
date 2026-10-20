<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('interest');
            $table->unique(['user_id', 'interest']);
        });

        Schema::create('game_libraries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();
            $table->unique(['user_id', 'game_id']);
        });

        Schema::create('guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('seeded_author')->nullable();
            $table->string('game');
            $table->string('title');
            $table->text('body');
            $table->string('type');
            $table->unsignedInteger('seeded_helpful')->default(0);
            $table->timestamps();
            $table->index(['type', 'created_at']);
            $table->index('game');
        });

        Schema::create('guide_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'guide_id']);
        });

        Schema::create('saved_guides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->unique(['user_id', 'guide_id']);
        });

        Schema::create('guide_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->string('game_name');
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->unsignedInteger('hours_played')->default(0);
            $table->string('seeded_author')->nullable();
            $table->timestamps();
            $table->index(['game_id', 'created_at']);
            $table->index(['game_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('guide_comments');
        Schema::dropIfExists('saved_guides');
        Schema::dropIfExists('guide_votes');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('game_libraries');
        Schema::dropIfExists('user_interests');
    }
};
