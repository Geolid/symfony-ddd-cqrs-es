<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TrustedDevice\Service;

use Iam\Authentication\Domain\TrustedDevice\Service\TrustedDeviceExpiryCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class TrustedDeviceExpiryCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesExpiresAt(): void
    {
        // Given
        $calculator = new TrustedDeviceExpiryCalculator(24 * 60 * 60);
        $trustedAt = Clock::get()->now();

        // When
        $expiresAt = $calculator->expiresAt($trustedAt);

        // Then
        self::assertSameDateTime($trustedAt->modify('+24 hours'), $expiresAt);
    }

    #[Test]
    public function itFailsWhenNotPositive(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new TrustedDeviceExpiryCalculator(0);
    }
}
