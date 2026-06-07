<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class ResolvesEmailRecipients
{
    /**
     * @return array<int, string>
     */
    public static function parse(string|array|null $input): array
    {
        if ($input === null || $input === '') {
            throw ValidationException::withMessages([
                'email' => 'Debe indicar al menos un destinatario.',
            ]);
        }

        $candidates = is_array($input)
            ? $input
            : (preg_split('/[,;]+/', (string) $input) ?: []);

        $normalized = [];

        foreach ($candidates as $candidate) {
            $email = strtolower(trim((string) $candidate));
            if ($email === '') {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'email' => "El correo «{$candidate}» no tiene un formato válido.",
                ]);
            }

            $normalized[] = $email;
        }

        $unique = array_values(array_unique($normalized));

        if ($unique === []) {
            throw ValidationException::withMessages([
                'email' => 'Debe indicar al menos un destinatario válido.',
            ]);
        }

        return $unique;
    }

    public static function formatForDisplay(array $recipients): string
    {
        return implode(', ', $recipients);
    }
}
