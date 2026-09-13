<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final class TaxRateTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(int $basisPoints): void
    {
        // When
        $taxRate = TaxRate::fromBasisPoints($basisPoints);

        // Then
        self::assertSame($basisPoints, $taxRate->basisPoints);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'rate' => [2_000];
        yield 'zero' => [0];
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
