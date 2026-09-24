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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenancy_id')->index();
            $table->uuid('category_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('status')->default('active');
            $table->uuid('user_id')->index();
            $table->jsonb('meta')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('image')->nullable(); //Ainda nao sei se vou implementar isso aq, mas vamo deixar aq.
            $table->timestamps();

            $table->foreign('tenancy_id')->references('id')->on('tenancies');
            $table->foreign(['tenancy_id', 'user_id'])->references(['tenancy_id', 'id'])->on('users');
            $table->foreign(['tenancy_id', 'category_id'])->references(['tenancy_id', 'id'])->on('categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
