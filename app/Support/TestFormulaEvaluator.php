<?php

namespace App\Support;

use App\Models\Test;

class TestFormulaEvaluator
{
    /**
     * @param  array<string, mixed>|null  $definition
     * @param  array<int, string|null>  $valuesByTestId
     */
    public function evaluate(?array $definition, array $valuesByTestId, ?int $decimals = 2): ?string
    {
        if ($definition === null || empty($definition['tokens'])) {
            return null;
        }

        $numeric = $this->buildNumericExpression($definition['tokens'], $valuesByTestId);
        if ($numeric === null) {
            return null;
        }

        $result = $this->evaluateNumericExpression($numeric);
        if ($result === null) {
            return null;
        }

        return $this->formatNumber($result, $decimals ?? 2);
    }

    public function evaluateForTest(Test $calculated, array $valuesByTestId): ?string
    {
        if (! $calculated->hasFormula()) {
            return null;
        }

        return $this->evaluate(
            $calculated->formulaDefinition(),
            $valuesByTestId,
            $calculated->decimals ?? 2
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $tokens
     */
    private function buildNumericExpression(array $tokens, array $valuesByTestId): ?string
    {
        $parts = [];

        foreach ($tokens as $token) {
            $type = $token['type'] ?? null;

            if ($type === 'test') {
                $testId = (int) ($token['test_id'] ?? 0);
                if ($testId <= 0) {
                    return null;
                }

                $raw = $valuesByTestId[$testId] ?? null;
                $numeric = self::parseNumeric($raw);
                if ($numeric === null) {
                    return null;
                }

                $parts[] = self::formatFloatForExpression($numeric);
            } elseif ($type === 'number') {
                $numeric = self::parseNumeric($token['value'] ?? null);
                if ($numeric === null) {
                    return null;
                }

                $parts[] = self::formatFloatForExpression($numeric);
            } elseif ($type === 'op') {
                $op = $token['value'] ?? '';
                if (! in_array($op, ['+', '-', '*', '/'], true)) {
                    return null;
                }
                $parts[] = $op;
            } elseif ($type === 'paren') {
                $paren = $token['value'] ?? '';
                if (! in_array($paren, ['(', ')'], true)) {
                    return null;
                }
                $parts[] = $paren;
            } else {
                return null;
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function evaluateNumericExpression(string $expression): ?float
    {
        try {
            $tokens = preg_split('/\s+/', trim($expression));
            if ($tokens === false || $tokens === []) {
                return null;
            }

            $output = [];
            $operators = [];
            $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

            foreach ($tokens as $token) {
                if (is_numeric($token)) {
                    $output[] = (float) $token;
                } elseif ($token === '(') {
                    $operators[] = $token;
                } elseif ($token === ')') {
                    while ($operators !== [] && end($operators) !== '(') {
                        $this->applyOperator($output, array_pop($operators));
                    }
                    if ($operators === [] || array_pop($operators) !== '(') {
                        return null;
                    }
                } elseif (isset($precedence[$token])) {
                    while (
                        $operators !== []
                        && end($operators) !== '('
                        && $precedence[end($operators)] >= $precedence[$token]
                    ) {
                        $this->applyOperator($output, array_pop($operators));
                    }
                    $operators[] = $token;
                } else {
                    return null;
                }
            }

            while ($operators !== []) {
                $op = array_pop($operators);
                if ($op === '(' || $op === ')') {
                    return null;
                }
                $this->applyOperator($output, $op);
            }

            if (count($output) !== 1) {
                return null;
            }

            return (float) $output[0];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<float>  $output
     */
    private function applyOperator(array &$output, string $operator): void
    {
        if (count($output) < 2) {
            throw new \RuntimeException('Invalid expression');
        }

        $b = array_pop($output);
        $a = array_pop($output);

        if ($operator === '/' && $b == 0.0) {
            throw new \DivisionByZeroError;
        }

        $result = match ($operator) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            '/' => $a / $b,
            default => throw new \RuntimeException('Unknown operator'),
        };

        $output[] = $result;
    }

    public static function parseNumeric(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $trimmed);
        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private static function formatFloatForExpression(float $value): string
    {
        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }

    public function formatNumber(float $value, int $decimals): string
    {
        $decimals = max(0, min(6, $decimals));

        return number_format($value, $decimals, '.', '');
    }
}
