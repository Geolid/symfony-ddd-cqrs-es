<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Specification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Service\CooldownCalculator;
use Shared\Domain\Specification\CooldownElapsedSpecification;
use Symfony\Component\Clock\Clock;

final class CooldownElapsedSpecificationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideElapsedCooldowns')]
    public function itIsSatisfiedWhenCooldownElapsed(?\DateTimeImmutable $lastRequestedAt, \DateTimeImmutable $now): void
    {
        // Given
        $specification = new CooldownElapsedSpecification(new CooldownCalculator(\DateInterval::createFromDateString('1 minute')), $now);

        // When
        $result = $specification->isSatisfiedBy($lastRequestedAt);

        // Then
        self::assertTrue($result);
    }

    /**
     * @return iterable<string, array{?\DateTimeImmutable, \DateTimeImmutable}>
     */
    public static function provideElapsedCooldowns(): iterable
    {
        $now = Clock::get()->now();

        yield 'no prior request' => [null, $now];
        yield 'exactly one cooldown ago' => [$now->modify('-1 minute'), $now];
        yield 'well past the cooldown' => [$now->modify('-90 seconds'), $now];
    }

    #[Test]
    #[DataProvider('provideRunningCooldowns')]
    public function itIsNotSatisfiedWhenCooldownRunning(\DateTimeImmutable $lastRequestedAt, \DateTimeImmutable $now): void
    {
        // Given
        $specification = new CooldownElapsedSpecification(new CooldownCalculator(\DateInterval::createFromDateString('1 minute')), $now);

        // When
        $result = $specification->isSatisfiedBy($lastRequestedAt);

        // Then
        self::assertFalse($result);
    }

    /**
     * @return iterable<string, array{\DateTimeImmutable, \DateTimeImmutable}>
     */
    public static function provideRunningCooldowns(): iterable
    {
        $now = Clock::get()->now();

        yield 'requested just now' => [$now, $now];
        yield 'half a cooldown ago' => [$now->modify('-30 seconds'), $now];
        yield 'one second left' => [$now->modify('-59 seconds'), $now];
    }
}
