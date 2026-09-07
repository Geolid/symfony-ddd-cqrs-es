<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Domain\Specification;

use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;

final class ErasureRetentionExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $requestedAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new ErasureRetentionExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($requestedAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $now = Clock::get()->now();

        yield 'within retention' => [$now->modify('-15 days'), $now, false];
        yield 'at retention boundary' => [$now->modify('-30 days'), $now, false];
        yield 'past retention' => [$now->modify('-45 days'), $now, true];
    }
}
