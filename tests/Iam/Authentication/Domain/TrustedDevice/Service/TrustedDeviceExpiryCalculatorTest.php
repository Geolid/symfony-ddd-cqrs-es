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
        $calculator = new TrustedDeviceExpiryCalculator(\DateInterval::createFromDateString('24 hours'));
        $trustedAt = Clock::get()->now();

        // When
        $expiresAt = $calculator->expiresAt($trustedAt);

        // Then
        self::assertSameDateTime($trustedAt->modify('+24 hours'), $expiresAt);
    }
}
