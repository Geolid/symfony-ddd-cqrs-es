<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\Service;

use Iam\Identity\Domain\Service\PendingIdentityExpiryCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class PendingIdentityExpiryCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesExpiresAt(): void
    {
        // Given
        $calculator = new PendingIdentityExpiryCalculator(12 * 60 * 60);
        $registeredAt = Clock::get()->now();

        // When
        $expiresAt = $calculator->expiresAt($registeredAt);

        // Then
        self::assertSameDateTime($registeredAt->modify('+12 hours'), $expiresAt);
    }

    #[Test]
    public function itFailsWhenNotPositive(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new PendingIdentityExpiryCalculator(0);
    }
}
