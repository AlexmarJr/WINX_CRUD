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
        Schema::create('tenancies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->string('status')->default('active');
            $table->jsonb('meta')->nullable(); //Não sei bem oq fazer com isso aq mas no geral sempre gosto de criar um jsonzinho
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenancies');
    }
};
