<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Domain\Service\CheckoutSessionExpiryCalculator;
use Support\TestCase\AssertionTrait;
use Symfony\Component\Clock\Clock;

final class CheckoutSessionExpiryCalculatorTest extends TestCase
{
    use AssertionTrait;

    #[Test]
    public function itCalculatesExpiresAt(): void
    {
        // Given
        $calculator = new CheckoutSessionExpiryCalculator(45);
        $openedAt = Clock::get()->now();

        // When
        $expiresAt = $calculator->expiresAt($openedAt);

        // Then
        self::assertSameDateTime($openedAt->modify('+45 minutes'), $expiresAt);
    }

    #[Test]
    public function itFailsWhenNotPositive(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        new CheckoutSessionExpiryCalculator(0);
    }
}
