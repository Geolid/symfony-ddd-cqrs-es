<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

enum DummyUniqueKey: string
{
    case NAME = 'dummy.name';
    case CODE = 'dummy.code';
}
