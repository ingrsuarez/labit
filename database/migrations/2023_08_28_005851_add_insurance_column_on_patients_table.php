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
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('insurance')->after('country')->nullable();
                $table->foreign('insurance')
                    ->references('id')
                    ->on('insurances')
                    ->onUpdate('cascade');
            $table->string('insurance_cod')->after('country');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
