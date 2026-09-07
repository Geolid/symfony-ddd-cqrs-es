<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\ValueObject;

enum SubjectState: string
{
    case RETAINED = 'retained';
    case ERASING = 'erasing';
    case ERASED = 'erased';

    public function isErasing(): bool
    {
        return self::ERASING === $this;
    }

    public function isErased(): bool
    {
        return self::ERASED === $this;
    }
}
