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
        Schema::create('maid_profiles', function (Blueprint $table) {
            $table->id();

            // Link to users table
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Identity verification
            $table->string('national_id_number')->unique();
            $table->string('national_id_photo_path');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                  ->default('pending');

            // Profile info
            $table->text('bio')->nullable();
            $table->string('service_area')->nullable();      // e.g. "Ntinda, Kampala"
            $table->json('skills')->nullable();              // e.g. ["deep cleaning","laundry"]
            $table->boolean('available')->default(true);

            // Stats
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maid_profiles');
    }
};