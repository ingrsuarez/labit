<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class TestFormulaDefinition
{
    /**
     * @return array{tokens: array<int, array<string, mixed>>, expression_display: string}|null
     */
    public static function fromRequest(?string $json, bool $enabled, ?int $selfTestId = null): ?array
    {
        if (! $enabled) {
            return null;
        }

        if ($json === null || trim($json) === '') {
            throw ValidationException::withMessages([
                'formula_json' => 'Defina al menos una práctica en la fórmula.',
            ]);
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded) || ! isset($decoded['tokens']) || ! is_array($decoded['tokens'])) {
            throw ValidationException::withMessages([
                'formula_json' => 'La fórmula no es válida.',
            ]);
        }

        $tokens = $decoded['tokens'];
        self::validateTokens($tokens, $selfTestId);

        return [
            'tokens' => $tokens,
            'expression_display' => self::buildDisplay($tokens),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tokens
     */
    public static function validateTokens(array $tokens, ?int $selfTestId = null): void
    {
        $hasTest = false;
        $openParens = 0;
        $lastType = null;

        foreach ($tokens as $index => $token) {
            $type = $token['type'] ?? null;

            if ($type === 'test') {
                $hasTest = true;
                $testId = (int) ($token['test_id'] ?? 0);
                if ($testId <= 0) {
                    throw ValidationException::withMessages([
                        'formula_json' => 'Práctica inválida en la fórmula.',
                    ]);
                }
                if ($selfTestId !== null && $testId === $selfTestId) {
                    throw ValidationException::withMessages([
                        'formula_json' => 'La fórmula no puede incluir esta misma determinación.',
                    ]);
                }
                if ($lastType === 'test') {
                    throw ValidationException::withMessages([
                        'formula_json' => 'Falta un operador entre prácticas.',
                    ]);
                }
                $lastType = 'test';
            } elseif ($type === 'op') {
                $op = $token['value'] ?? '';
                if (! in_array($op, ['+', '-', '*', '/'], true)) {
                    throw ValidationException::withMessages([
                        'formula_json' => 'Operador no permitido.',
                    ]);
                }
                if ($lastType === null || $lastType === 'op' || $lastType === 'paren_open') {
                    throw ValidationException::withMessages([
                        'formula_json' => 'La expresión no puede empezar o tener dos operadores seguidos.',
                    ]);
                }
                $lastType = 'op';
            } elseif ($type === 'paren') {
                $paren = $token['value'] ?? '';
                if ($paren === '(') {
                    $openParens++;
                    if ($lastType === 'test' || ($lastType === 'paren' && ($tokens[$index - 1]['value'] ?? '') === ')')) {
                        throw ValidationException::withMessages([
                            'formula_json' => 'Falta un operador antes del paréntesis.',
                        ]);
                    }
                    $lastType = 'paren_open';
                } elseif ($paren === ')') {
                    $openParens--;
                    if ($openParens < 0) {
                        throw ValidationException::withMessages([
                            'formula_json' => 'Revise los paréntesis de la expresión.',
                        ]);
                    }
                    if ($lastType === 'op' || $lastType === 'paren_open') {
                        throw ValidationException::withMessages([
                            'formula_json' => 'Paréntesis mal ubicado.',
                        ]);
                    }
                    $lastType = 'paren';
                } else {
                    throw ValidationException::withMessages([
                        'formula_json' => 'Paréntesis inválido.',
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'formula_json' => 'Token de fórmula inválido.',
                ]);
            }
        }

        if (! $hasTest) {
            throw ValidationException::withMessages([
                'formula_json' => 'Defina al menos una práctica en la fórmula.',
            ]);
        }

        if ($openParens !== 0) {
            throw ValidationException::withMessages([
                'formula_json' => 'Revise los paréntesis de la expresión.',
            ]);
        }

        if ($lastType === 'op' || $lastType === 'paren_open') {
            throw ValidationException::withMessages([
                'formula_json' => 'La expresión no puede terminar en un operador.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tokens
     */
    public static function buildDisplay(array $tokens): string
    {
        $parts = [];

        foreach ($tokens as $token) {
            if (($token['type'] ?? '') === 'test') {
                $parts[] = $token['name'] ?? $token['code'] ?? '?';
            } elseif (($token['type'] ?? '') === 'op') {
                $parts[] = match ($token['value'] ?? '') {
                    '*' => '×',
                    '/' => '÷',
                    default => $token['value'] ?? '',
                };
            } elseif (($token['type'] ?? '') === 'paren') {
                $parts[] = $token['value'] ?? '';
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>|null  $definition
     * @return array<int, int>
     */
    public static function referencedTestIds(?array $definition): array
    {
        if ($definition === null || empty($definition['tokens'])) {
            return [];
        }

        $ids = [];
        foreach ($definition['tokens'] as $token) {
            if (($token['type'] ?? '') === 'test') {
                $ids[] = (int) ($token['test_id'] ?? 0);
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
