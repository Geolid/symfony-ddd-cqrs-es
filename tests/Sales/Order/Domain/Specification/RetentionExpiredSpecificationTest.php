<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\Specification\RetentionExpiredSpecification;

final class RetentionExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $closedAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new RetentionExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($closedAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $closedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        yield 'within retention period' => [$closedAt, new \DateTimeImmutable('2030-01-01T00:00:00+00:00'), false];
        yield 'at retention period boundary' => [$closedAt, $closedAt->modify('+3650 days'), false];
        yield 'past retention period' => [$closedAt, new \DateTimeImmutable('2036-01-01T00:00:00+00:00'), true];
    }
}
