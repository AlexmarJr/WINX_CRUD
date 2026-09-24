<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invites', function (Blueprint $table) {
            $table->foreignUuid('tenancy_id')->constrained('tenancies');
            $table->string('token')->unique();
            $table->string('role')->default('employee');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
        });

        DB::statement("CREATE UNIQUE INDEX invites_pending_email_unique ON invites (lower(email)) WHERE status = 'pending' AND deleted_at IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS invites_pending_email_unique');

        Schema::table('invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenancy_id');
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'role', 'expires_at', 'accepted_at']);
        });
    }
};
