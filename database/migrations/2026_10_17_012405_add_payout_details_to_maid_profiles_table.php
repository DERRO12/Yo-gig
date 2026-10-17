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
        Schema::table('maid_profiles', function (Blueprint $table) {
            $table->string('payout_method')->nullable()->after('average_rating');
            $table->string('payout_account_name')->nullable()->after('payout_method');
            $table->string('payout_account_number')->nullable()->after('payout_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maid_profiles', function (Blueprint $table) {
            $table->dropColumn(['payout_method', 'payout_account_name', 'payout_account_number']);
        });
    }
};
