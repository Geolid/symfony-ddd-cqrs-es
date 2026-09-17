<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Domain\Specification;

use Iam\Identity\Domain\Specification\PendingIdentityExpiredSpecification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;

final class PendingIdentityExpiredSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $registeredAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new PendingIdentityExpiredSpecification($now);

        // When
        $result = $specification->isSatisfiedBy($registeredAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $now = Clock::get()->now();

        yield 'within window' => [$now->modify('-12 hours'), $now, false];
        yield 'at window boundary' => [$now->modify('-24 hours'), $now, false];
        yield 'past window' => [$now->modify('-25 hours'), $now, true];
    }
}
