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
        Schema::table('users', function (Blueprint $table) {
            // Change column from ENUM to VARCHAR to allow 'super_admin' and any future roles
            $table->string('role')->default('client')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to original ENUM definition if rolled back
            $table->enum('role', ['client', 'maid', 'admin'])->default('client')->change();
        });
    }
};
