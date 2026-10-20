<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        Schema::create('traffic_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 255);
            $table->string('method', 10);
            $table->unsignedSmallInteger('status');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_events');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }
};
