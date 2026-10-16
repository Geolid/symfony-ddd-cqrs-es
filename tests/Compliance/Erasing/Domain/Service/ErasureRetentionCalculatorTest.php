<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Domain\Service;

use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class ErasureRetentionCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesEffectiveAt(): void
    {
        // Given
        $calculator = new ErasureRetentionCalculator(\DateInterval::createFromDateString('7 days'));
        $requestedAt = Clock::get()->now();

        // When
        $effectiveAt = $calculator->effectiveAt($requestedAt);

        // Then
        self::assertSameDateTime($requestedAt->modify('+7 days'), $effectiveAt);
    }
}
