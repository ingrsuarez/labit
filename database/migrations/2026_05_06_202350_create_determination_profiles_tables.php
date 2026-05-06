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
        Schema::create('determination_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lab_type', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['lab_type', 'is_active']);
        });

        Schema::create('determination_profile_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('determination_profile_id')->constrained('determination_profiles')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['determination_profile_id', 'test_id'], 'det_prof_test_pid_tid_unique');
        });

        Schema::create('determination_profile_applications', function (Blueprint $table) {
            $table->id();
            $table->string('applicable_type');
            $table->unsignedBigInteger('applicable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('profiles_snapshot');
            $table->unsignedInteger('tests_added_count')->default(0);
            $table->unsignedInteger('tests_skipped_duplicate_count')->default(0);
            $table->json('skipped_details')->nullable();
            $table->timestamps();

            $table->index(['applicable_type', 'applicable_id'], 'det_prof_app_applicable_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('determination_profile_applications');
        Schema::dropIfExists('determination_profile_test');
        Schema::dropIfExists('determination_profiles');
    }
};
