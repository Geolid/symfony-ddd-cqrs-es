<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application;

use Iam\Identity\Application\IdentityStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IdentityStatusTest extends TestCase
{
    #[Test]
    public function itIsActive(): void
    {
        foreach (IdentityStatus::cases() as $status) {
            self::assertSame(IdentityStatus::ACTIVE === $status, $status->isActive(), $status->value);
        }
    }
}
