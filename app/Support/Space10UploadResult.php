<?php

namespace App\Support;

class Space10UploadResult
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_ERROR = 'error';

    public const STATUS_DISABLED = 'disabled';

    public function __construct(
        public readonly string $status,
        public readonly ?string $message = null,
    ) {}

    public static function success(?string $message = null): self
    {
        return new self(self::STATUS_SUCCESS, $message);
    }

    public static function skipped(string $message): self
    {
        return new self(self::STATUS_SKIPPED, $message);
    }

    public static function error(string $message): self
    {
        return new self(self::STATUS_ERROR, $message);
    }

    public static function disabled(): self
    {
        return new self(self::STATUS_DISABLED, 'Space10 deshabilitado');
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function isError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}
