<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Enum;

use Iam\Identity\Application\Enum\AppIdentityStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AppIdentityStatusTest extends TestCase
{
    #[Test]
    public function itIsActiveOnlyWhenActive(): void
    {
        foreach (AppIdentityStatus::cases() as $status) {
            self::assertSame(AppIdentityStatus::ACTIVE === $status, $status->isActive(), $status->value);
        }
    }
}
