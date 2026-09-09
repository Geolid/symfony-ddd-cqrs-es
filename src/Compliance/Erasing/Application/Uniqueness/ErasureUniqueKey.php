<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Uniqueness;

enum ErasureUniqueKey: string
{
    case IDENTITY = 'compliance.erasing.erasure.identity';
}
