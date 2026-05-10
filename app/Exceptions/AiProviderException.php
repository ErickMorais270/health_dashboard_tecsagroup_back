<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AiProviderException extends RuntimeException
{
    public static function fromHttpStatus(int $status, string $message): self
    {
        return new self($message, $status);
    }
}
