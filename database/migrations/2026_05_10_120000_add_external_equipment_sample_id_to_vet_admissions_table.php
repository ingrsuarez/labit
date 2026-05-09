<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->string('external_equipment_sample_id', 50)->nullable()->after('protocol_number');
            $table->index('external_equipment_sample_id', 'vet_admissions_ext_sample_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vet_admissions', function (Blueprint $table) {
            $table->dropIndex('vet_admissions_ext_sample_id_idx');
            $table->dropColumn('external_equipment_sample_id');
        });
    }
};
