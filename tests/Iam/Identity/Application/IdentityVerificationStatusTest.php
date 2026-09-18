<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application;

use Iam\Identity\Application\IdentityVerificationStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityVerificationStatusTest extends TestCase
{
    #[Test]
    public function itIsPending(): void
    {
        foreach (IdentityVerificationStatus::cases() as $status) {
            self::assertSame(IdentityVerificationStatus::PENDING === $status, $status->isPending(), $status->value);
        }
    }

    #[Test]
    public function itIsConfirmed(): void
    {
        foreach (IdentityVerificationStatus::cases() as $status) {
            self::assertSame(IdentityVerificationStatus::CONFIRMED === $status, $status->isConfirmed(), $status->value);
        }
    }
}
