<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('admissions')
            ->select('protocol_number', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('protocol_number')
            ->groupBy('protocol_number')
            ->having('duplicate_count', '>', 1)
            ->pluck('protocol_number');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede agregar UNIQUE en admissions.protocol_number: duplicados encontrados: '
                .$duplicates->implode(', ')
            );
        }

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['protocol_number']);
            $table->unique('protocol_number');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropUnique(['protocol_number']);
            $table->index('protocol_number');
        });
    }
};
