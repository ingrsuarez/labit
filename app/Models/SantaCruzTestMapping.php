<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantaCruzTestMapping extends Model
{
    protected $fillable = [
        'prestacion_code',
        'prestacion_name',
        'test_id',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public static function normalizePrestacionCode(string $code): string
    {
        $code = trim($code);

        return mb_strtolower(preg_replace('/\.+/', '', $code) ?? $code);
    }

    /**
     * Resolución: tabla por código normalizado → match por código en tests → match por nombre en tests.
     */
    public static function resolveTestId(string $prestacionCode, string $prestacionName): ?int
    {
        $norm = self::normalizePrestacionCode($prestacionCode);
        if ($norm !== '') {
            $row = self::query()->where('prestacion_code', $norm)->first();
            if ($row) {
                return (int) $row->test_id;
            }

            $testId = self::matchTestByCodeVariants($prestacionCode);
            if ($testId !== null) {
                return $testId;
            }
        }

        $name = trim($prestacionName);
        if ($name !== '') {
            $testId = Test::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->value('id');
            if ($testId) {
                return (int) $testId;
            }
        }

        return null;
    }

    private static function matchTestByCodeVariants(string $rawCode): ?int
    {
        $variants = array_unique(array_filter([
            trim($rawCode),
            self::normalizePrestacionCode($rawCode),
            str_replace('.', '', trim($rawCode)),
        ]));

        foreach ($variants as $v) {
            if ($v === '') {
                continue;
            }
            $id = Test::query()
                ->whereRaw('LOWER(TRIM(code)) = ?', [mb_strtolower($v)])
                ->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }
}
