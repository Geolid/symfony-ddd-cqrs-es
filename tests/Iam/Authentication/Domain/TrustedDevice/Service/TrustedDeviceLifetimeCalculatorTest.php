<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Domain\TrustedDevice\Service;

use Iam\Authentication\Domain\TrustedDevice\Service\TrustedDeviceLifetimeCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class TrustedDeviceLifetimeCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesExpiresAt(): void
    {
        // Given
        $calculator = new TrustedDeviceLifetimeCalculator(3600);
        $trustedAt = Clock::get()->now();

        // When
        $expiresAt = $calculator->expiresAt($trustedAt);

        // Then
        self::assertSameDateTime($trustedAt->modify('+3600 seconds'), $expiresAt);
    }
}
