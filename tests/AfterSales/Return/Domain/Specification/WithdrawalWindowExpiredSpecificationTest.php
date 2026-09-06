<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Domain\Specification;

use AfterSales\Return\Domain\Specification\WithdrawalWindowExpiredSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;

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
        $now = Clock::get()->now();

        yield 'within window' => [$now->modify('-9 days'), $now, false];
        yield 'at window boundary' => [$now->modify('-14 days'), $now, false];
        yield 'past window' => [$now->modify('-19 days'), $now, true];
    }
}
