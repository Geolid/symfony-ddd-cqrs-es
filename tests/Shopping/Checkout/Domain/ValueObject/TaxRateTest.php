<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final class TaxRateTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // When
        $taxRate = TaxRate::fromBasisPoints(2_000);

        // Then
        self::assertSame(2_000, $taxRate->basisPoints);
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        TaxRate::fromBasisPoints(-1);
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $a = TaxRate::fromBasisPoints(2_000);
        $b = TaxRate::fromBasisPoints(2_000);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $a = TaxRate::fromBasisPoints(2_000);
        $b = TaxRate::fromBasisPoints(1_900);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertFalse($equals);
    }
}
