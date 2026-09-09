<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\ValueObject;

enum ErasureUniqueKey: string
{
    case IDENTITY = 'compliance.erasing.erasure.identity';
}
