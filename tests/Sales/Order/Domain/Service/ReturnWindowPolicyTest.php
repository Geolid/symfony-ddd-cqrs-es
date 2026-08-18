<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\Service\ReturnWindowPolicy;

final class ReturnWindowPolicyTest extends TestCase
{
    #[Test]
    public function itHasNotExpiredWithinWindow(): void
    {
        // Given
        $policy = new ReturnWindowPolicy(14);
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $policy->hasExpired($deliveredAt, new \DateTimeImmutable('2026-01-10T00:00:00+00:00'));

        // Then
        self::assertFalse($expired);
    }

    #[Test]
    public function itHasExpiredPastWindow(): void
    {
        // Given
        $policy = new ReturnWindowPolicy(14);
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $policy->hasExpired($deliveredAt, new \DateTimeImmutable('2026-01-20T00:00:00+00:00'));

        // Then
        self::assertTrue($expired);
    }

    #[Test]
    public function itComputesCutoff(): void
    {
        // Given
        $policy = new ReturnWindowPolicy(14);
        $now = new \DateTimeImmutable('2026-01-20T00:00:00+00:00');

        // When
        $cutoff = $policy->cutoffFor($now);

        // Then
        self::assertSame('2026-01-06T00:00:00+00:00', $cutoff->format(\DateTimeInterface::ATOM));
    }
}
