<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SyncsEntityEmails
{
    private const LABEL_PRESETS = ['Resultados', 'Facturación', 'Pagos'];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function sync(Model $entity, array $rows): void
    {
        $normalized = $this->normalizeRows($rows);

        $this->assertNoDuplicateEmails($normalized);

        $entity->emails()->delete();

        $primaryEmail = null;

        foreach ($normalized as $index => $row) {
            $entity->emails()->create([
                'email' => $row['email'],
                'label' => $row['label'],
                'is_primary' => $row['is_primary'],
                'sort_order' => $index,
            ]);

            if ($row['is_primary']) {
                $primaryEmail = $row['email'];
            }
        }

        if ($primaryEmail === null && $normalized !== []) {
            $primaryEmail = $normalized[0]['email'];
            $entity->emails()->where('email', $primaryEmail)->update(['is_primary' => true]);
        }

        $entity->forceFill(['email' => $primaryEmail])->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{email: string, label: ?string, is_primary: bool}>
     */
    private function normalizeRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }

            $validator = Validator::make(['email' => $email], [
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'emails' => 'Uno o más correos no tienen un formato válido.',
                ]);
            }

            $label = $this->resolveLabel($row);

            $normalized[] = [
                'email' => $email,
                'label' => $label !== '' ? $label : null,
                'is_primary' => filter_var($row['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        if ($normalized === []) {
            return [];
        }

        $hasPrimary = collect($normalized)->contains(fn (array $r) => $r['is_primary']);
        if (! $hasPrimary) {
            $normalized[0]['is_primary'] = true;
        } else {
            $primarySet = false;
            foreach ($normalized as $i => $row) {
                if ($row['is_primary']) {
                    if ($primarySet) {
                        $normalized[$i]['is_primary'] = false;
                    } else {
                        $primarySet = true;
                    }
                }
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveLabel(array $row): string
    {
        $preset = trim((string) ($row['label_preset'] ?? $row['label'] ?? ''));

        if ($preset === 'Otro') {
            return trim((string) ($row['label_custom'] ?? ''));
        }

        if (in_array($preset, self::LABEL_PRESETS, true)) {
            return $preset;
        }

        return $preset;
    }

    /**
     * @param  array<int, array{email: string, label: ?string, is_primary: bool}>  $rows
     */
    private function assertNoDuplicateEmails(array $rows): void
    {
        $emails = array_map(fn (array $r) => strtolower($r['email']), $rows);

        if (count($emails) !== count(array_unique($emails))) {
            throw ValidationException::withMessages([
                'emails' => 'No se permiten correos duplicados.',
            ]);
        }
    }
}
