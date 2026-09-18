<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application;

use Iam\Identity\Application\IdentityModerationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityModerationStatusTest extends TestCase
{
    #[Test]
    public function itIsActive(): void
    {
        foreach (IdentityModerationStatus::cases() as $status) {
            self::assertSame(IdentityModerationStatus::ACTIVE === $status, $status->isActive(), $status->value);
        }
    }

    #[Test]
    public function itIsSuspended(): void
    {
        foreach (IdentityModerationStatus::cases() as $status) {
            self::assertSame(IdentityModerationStatus::SUSPENDED === $status, $status->isSuspended(), $status->value);
        }
    }
}
