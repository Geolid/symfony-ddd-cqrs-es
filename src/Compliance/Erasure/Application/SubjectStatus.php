<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application;

enum SubjectStatus: string
{
    case RETAINED = 'retained';
    case ERASING = 'erasing';
    case ERASED = 'erased';
}
