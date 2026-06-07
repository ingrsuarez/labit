<?php

namespace App\Models\Concerns;

use App\Models\EntityEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEntityEmails
{
    public static function bootHasEntityEmails(): void
    {
        static::deleting(function (Model $model) {
            if (method_exists($model, 'emails')) {
                $model->emails()->delete();
            }
        });
    }

    public function emails(): MorphMany
    {
        return $this->morphMany(EntityEmail::class, 'emailable')
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return array<int, string>
     */
    public function recipientEmails(): array
    {
        $emails = $this->relationLoaded('emails')
            ? $this->emails
            : $this->emails()->get();

        if ($emails->isNotEmpty()) {
            return $emails->pluck('email')->unique()->values()->all();
        }

        if (! empty($this->email)) {
            return [$this->email];
        }

        return [];
    }

    public function primaryEntityEmail(): ?string
    {
        $primary = $this->emails()->where('is_primary', true)->value('email');

        return $primary ?? $this->email;
    }

    public function extraEmailsCount(): int
    {
        $count = $this->relationLoaded('emails')
            ? $this->emails->count()
            : $this->emails()->count();

        return max(0, $count - 1);
    }

    public function emailsTooltip(): string
    {
        $emails = $this->relationLoaded('emails')
            ? $this->emails
            : $this->emails()->get();

        return $emails->map(function (EntityEmail $e) {
            $label = $e->label ? "{$e->label}: " : '';

            return $label.$e->email;
        })->implode(' · ');
    }
}
