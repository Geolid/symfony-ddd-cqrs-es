<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Domain\Specification;

use AfterSales\Withdrawal\Domain\Specification\WithdrawalWindowExpiredSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WithdrawalWindowExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $deliveredAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new WithdrawalWindowExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($deliveredAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $deliveredAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        yield 'within window' => [$deliveredAt, $deliveredAt->modify('+9 days'), false];
        yield 'at window boundary' => [$deliveredAt, $deliveredAt->modify('+14 days'), false];
        yield 'past window' => [$deliveredAt, $deliveredAt->modify('+19 days'), true];
    }
}
