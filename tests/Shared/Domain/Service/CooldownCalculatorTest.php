<?php

declare(strict_types=1);

namespace Shared\Tests\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Service\CooldownCalculator;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class CooldownCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesRetryAt(): void
    {
        // Given
        $calculator = new CooldownCalculator(\DateInterval::createFromDateString('5 minutes'));
        $lastRequestedAt = Clock::get()->now();

        // When
        $retryAt = $calculator->retryAt($lastRequestedAt);

        // Then
        self::assertSameDateTime($lastRequestedAt->modify('+5 minutes'), $retryAt);
    }
}
