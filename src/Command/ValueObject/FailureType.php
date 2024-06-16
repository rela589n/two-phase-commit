<?php

declare(strict_types=1);

namespace App\Command\ValueObject;

use RuntimeException;

enum FailureType: string
{
    case EXCEPTION = 'exception';

    case FAULT = 'fault';

    public function raise(FailurePoint $point): void
    {
        match ($this) {
            self::EXCEPTION => throw new RuntimeException($point->value),
            self::FAULT => die(),
        };
    }
}
