<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Service\CooldownCalculator;
use Symfony\Component\Clock\Clock;

final class CooldownCalculatorTest extends TestCase
{
    #[Test]
    public function itCalculatesRetryAt(): void
    {
        // Given
        $calculator = new CooldownCalculator('+60 seconds');
        $lastRequestedAt = Clock::get()->now();

        // When
        $retryAt = $calculator->retryAt($lastRequestedAt);

        // Then
        self::assertSame($lastRequestedAt->modify('+60 seconds'), $retryAt);
    }

    #[Test]
    public function itDefaultsToSixtySeconds(): void
    {
        // Given
        $calculator = new CooldownCalculator();
        $lastRequestedAt = Clock::get()->now();

        // When
        $retryAt = $calculator->retryAt($lastRequestedAt);

        // Then
        self::assertSame($lastRequestedAt->modify(CooldownCalculator::DEFAULT_COOLDOWN), $retryAt);
    }
}
