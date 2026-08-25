<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\Service\ReturnWindow;

final class ReturnWindowTest extends TestCase
{
    #[Test]
    public function itHasNotExpiredWithinWindow(): void
    {
        // Given
        $returnWindow = new ReturnWindow(14);
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $returnWindow->hasExpired($deliveredAt, new \DateTimeImmutable('2026-01-10T00:00:00+00:00'));

        // Then
        self::assertFalse($expired);
    }

    #[Test]
    public function itHasNotExpiredAtWindowBoundary(): void
    {
        // Given
        $returnWindow = new ReturnWindow(14);
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $returnWindow->hasExpired($deliveredAt, $deliveredAt->modify('+14 days'));

        // Then
        self::assertFalse($expired);
    }

    #[Test]
    public function itHasExpiredPastWindow(): void
    {
        // Given
        $returnWindow = new ReturnWindow(14);
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        // When
        $expired = $returnWindow->hasExpired($deliveredAt, new \DateTimeImmutable('2026-01-20T00:00:00+00:00'));

        // Then
        self::assertTrue($expired);
    }

    #[Test]
    public function itComputesCutoff(): void
    {
        // Given
        $returnWindow = new ReturnWindow(14);
        $now = new \DateTimeImmutable('2026-01-20T00:00:00+00:00');

        // When
        $cutoff = $returnWindow->cutoffFor($now);

        // Then
        self::assertSame('2026-01-06T00:00:00+00:00', $cutoff->format(\DateTimeInterface::ATOM));
    }
}
