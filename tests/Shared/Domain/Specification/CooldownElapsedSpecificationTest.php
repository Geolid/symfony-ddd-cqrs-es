<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Specification\CooldownElapsedSpecification;
use Symfony\Component\Clock\Clock;

final class CooldownElapsedSpecificationTest extends TestCase
{
    #[Test]
    public function itIsSatisfiedByNoPriorRequest(): void
    {
        // Given
        $specification = new CooldownElapsedSpecification('+60 seconds', Clock::get()->now());

        // When
        $result = $specification->isSatisfiedBy(null);

        // Then
        self::assertTrue($result);
    }

    #[Test]
    #[DataProvider('provideThreshold')]
    public function itIsSatisfiedBy(\DateTimeImmutable $lastRequestedAt, \DateTimeImmutable $now, bool $expected): void
    {
        // Given
        $specification = new CooldownElapsedSpecification('+60 seconds', $now);

        // When
        $result = $specification->isSatisfiedBy($lastRequestedAt);

        // Then
        self::assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable, bool}>
     */
    public static function provideThreshold(): iterable
    {
        $now = Clock::get()->now();

        yield 'within cooldown' => [$now->modify('-30 seconds'), $now, false];
        yield 'at cooldown boundary' => [$now->modify('-60 seconds'), $now, true];
        yield 'past cooldown' => [$now->modify('-90 seconds'), $now, true];
    }
}
