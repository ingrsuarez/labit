<?php

use App\Models\Customer;
use App\Models\EntityEmail;
use App\Models\Insurance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_emails', function (Blueprint $table) {
            $table->id();
            $table->morphs('emailable');
            $table->string('email');
            $table->string('label', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['emailable_type', 'emailable_id', 'email']);
        });

        $this->backfillFromLegacy(Customer::class, 'customers');
        $this->backfillFromLegacy(Insurance::class, 'insurances');
    }

    private function backfillFromLegacy(string $modelClass, string $table): void
    {
        $rows = \DB::table($table)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email']);

        foreach ($rows as $row) {
            EntityEmail::query()->firstOrCreate(
                [
                    'emailable_type' => $modelClass,
                    'emailable_id' => $row->id,
                    'email' => $row->email,
                ],
                [
                    'label' => null,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_emails');
    }
};
