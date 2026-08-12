<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Order\Domain\ValueObject\OrderLine;
use Shared\Domain\ValueObject\Money;

final class OrderLineTest extends TestCase
{
    #[Test]
    #[DataProvider('provideAcceptedValues')]
    public function itCreates(string $label, string $expectedLabel): void
    {
        // When
        $line = OrderLine::of($label, 3, Money::fromCents(83));

        // Then
        self::assertSame($expectedLabel, $line->label);
        self::assertSame(3, $line->quantity);
        self::assertSame(83, $line->unitAmount->toCents());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAcceptedValues(): iterable
    {
        yield 'line' => ['Saucer', 'Saucer'];
        yield 'surrounding whitespace' => ['  Saucer  ', 'Saucer'];
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(string $label, int $quantity): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        OrderLine::of($label, $quantity, Money::fromCents(83));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty label' => ['', 1];
        yield 'whitespace only label' => ['   ', 1];
        yield 'zero quantity' => ['Saucer', 0];
        yield 'negative quantity' => ['Saucer', -1];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $a = OrderLine::of('Saucer', 3, Money::fromCents(83));
        $b = OrderLine::of('  Saucer  ', 3, Money::fromCents(83));
        $other = OrderLine::of('Saucer', 4, Money::fromCents(83));

        // When
        $equalResult = $a->equals($b);
        $differentResult = $a->equals($other);

        // Then
        self::assertTrue($equalResult);
        self::assertFalse($differentResult);
    }

    #[Test]
    public function itTotalsTheUnitAmountOverTheQuantity(): void
    {
        // When
        $total = OrderLine::of('Saucer', 3, Money::fromCents(83))->total();

        // Then
        self::assertSame(249, $total->toCents());
    }
}
