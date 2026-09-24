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
        Schema::create('logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->uuid('tenancy_id');

            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->uuid('entity_id');

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign(['tenancy_id', 'user_id'])->references(['tenancy_id', 'id'])->on('users');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
