<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\Specification\ReturnWindowExpiredSpecification;

final class ReturnWindowExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideExpiryChecks')]
    public function itIsSatisfiedBy(\DateTimeImmutable $deliveredAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new ReturnWindowExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($deliveredAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideExpiryChecks(): iterable
    {
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        yield 'within window' => [$deliveredAt, new \DateTimeImmutable('2026-01-10T00:00:00+00:00'), false];
        yield 'at window boundary' => [$deliveredAt, $deliveredAt->modify('+14 days'), false];
        yield 'past window' => [$deliveredAt, new \DateTimeImmutable('2026-01-20T00:00:00+00:00'), true];
    }
}
