<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

enum DummyUniqueKey: string
{
    case DISCRIMINATOR = 'dummy.discriminator';
    case OTHER_DISCRIMINATOR = 'dummy.other_discriminator';
}
