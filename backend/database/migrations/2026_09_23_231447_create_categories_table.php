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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenancy_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->jsonb('meta')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->uuid('user_id')->index();
            $table->timestamps();

            $table->unique(['tenancy_id', 'id']);

            $table->foreign('tenancy_id')->references('id')->on('tenancies');
            $table->foreign(['tenancy_id', 'user_id'])->references(['tenancy_id', 'id'])->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
