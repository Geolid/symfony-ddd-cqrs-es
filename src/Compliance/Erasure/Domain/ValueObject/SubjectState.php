<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\ValueObject;

enum SubjectState: string
{
    case RETAINED = 'retained';
    case ERASING = 'erasing';
    case ERASED = 'erased';
}
