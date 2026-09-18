<?php

declare(strict_types=1);

namespace Shared\Tests\Support\Double;

enum DummyVerificationCodePurpose: string
{
    case NAME = 'dummy.name';
    case OTHER = 'dummy.other';
}
