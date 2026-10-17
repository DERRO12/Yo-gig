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
        Schema::table('cleaning_jobs', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('id');
            $table->foreignId('service_id')->nullable()->after('maid_id')->constrained('services')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1)->after('budget');
            $table->decimal('platform_fee', 12, 2)->nullable()->after('quantity');
            $table->decimal('maid_earnings', 12, 2)->nullable()->after('platform_fee');
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->timestamp('started_at')->nullable()->after('claimed_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('client_approved_at')->nullable()->after('completed_at');
            $table->timestamp('cancelled_at')->nullable()->after('client_approved_at');
            $table->unsignedTinyInteger('client_rating')->nullable()->after('cancelled_at');
            $table->text('client_review')->nullable()->after('client_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropUnique(['reference']);
            $table->dropColumn([
                'reference',
                'quantity',
                'platform_fee',
                'maid_earnings',
                'payment_status',
                'started_at',
                'completed_at',
                'client_approved_at',
                'cancelled_at',
                'client_rating',
                'client_review',
            ]);
        });
    }
};
