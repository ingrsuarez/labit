<?php

namespace App\Enums;

enum DeterminationProfileLabType: string
{
    case Clinico = 'clinico';
    case Veterinario = 'veterinario';
    case AguasAlimentos = 'aguas_alimentos';

    public function label(): string
    {
        return match ($this) {
            self::Clinico => 'Clínico',
            self::Veterinario => 'Veterinario',
            self::AguasAlimentos => 'Aguas / alimentos',
        };
    }

    /**
     * Valor en tests.categories (JSON).
     */
    public function categoryKey(): string
    {
        return match ($this) {
            self::Clinico => 'clinico',
            self::Veterinario => 'veterinario',
            self::AguasAlimentos => 'aguas_alimentos',
        };
    }

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
