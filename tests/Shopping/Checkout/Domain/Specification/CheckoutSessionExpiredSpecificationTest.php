<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Domain\Specification\CheckoutSessionExpiredSpecification;
use Symfony\Component\Clock\Clock;

final class CheckoutSessionExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $openedAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new CheckoutSessionExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($openedAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $now = Clock::get()->now();

        yield 'within TTL' => [$now->modify('-15 minutes'), $now, false];
        yield 'at TTL boundary' => [$now->modify('-30 minutes'), $now, false];
        yield 'past TTL' => [$now->modify('-45 minutes'), $now, true];
    }
}
