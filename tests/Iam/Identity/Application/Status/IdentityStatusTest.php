<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Status;

use Iam\Identity\Application\Status\IdentityStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityStatusTest extends TestCase
{
    #[Test]
    public function itIsActiveOnlyWhenActive(): void
    {
        foreach (IdentityStatus::cases() as $status) {
            self::assertSame(IdentityStatus::ACTIVE === $status, $status->isActive(), $status->value);
        }
    }
}
