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
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('number');
            $table->unsignedBigInteger('room');
            $table->string('bed')->nullable();
            $table->unsignedBigInteger('institution');
            $table->unsignedBigInteger('service')->nullable();
            $table->unsignedBigInteger('applicant')->nullable();
            $table->date('invoice_date');
            $table->string('observations')->nullable();
            $table->date('promise_date');
            $table->unsignedBigInteger('insurance')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('authorization_code');
            $table->unsignedBigInteger('attended_by');
            $table->float('insurance_price');
            $table->float('patient_price');
            $table->unsignedBigInteger('cash');
            $table->unsignedBigInteger('created_by');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
