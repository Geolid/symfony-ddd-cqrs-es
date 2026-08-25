<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\Service\RetentionWindow;

final class RetentionWindowTest extends TestCase
{
    #[Test]
    public function itHasNotExpiredWithinRetentionPeriod(): void
    {
        // Given
        $retentionWindow = new RetentionWindow(3650);
        $closedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $retentionWindow->hasExpired($closedAt, new \DateTimeImmutable('2030-01-01T00:00:00+00:00'));

        // Then
        self::assertFalse($expired);
    }

    #[Test]
    public function itHasNotExpiredAtRetentionPeriodBoundary(): void
    {
        // Given
        $retentionWindow = new RetentionWindow(3650);
        $closedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $retentionWindow->hasExpired($closedAt, $closedAt->modify('+3650 days'));

        // Then
        self::assertFalse($expired);
    }

    #[Test]
    public function itHasExpiredPastRetentionPeriod(): void
    {
        // Given
        $retentionWindow = new RetentionWindow(3650);
        $closedAt = new \DateTimeImmutable('2016-01-01T00:00:00+00:00');

        // When
        $expired = $retentionWindow->hasExpired($closedAt, new \DateTimeImmutable('2036-01-01T00:00:00+00:00'));

        // Then
        self::assertTrue($expired);
    }

    #[Test]
    public function itComputesCutoff(): void
    {
        // Given
        $retentionWindow = new RetentionWindow(3650);
        $now = new \DateTimeImmutable('2036-01-01T00:00:00+00:00');

        // When
        $cutoff = $retentionWindow->cutoffFor($now);

        // Then
        self::assertSame('2026-01-03T00:00:00+00:00', $cutoff->format(\DateTimeInterface::ATOM));
    }
}
