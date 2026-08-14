<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class OrderLineTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $product = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));

        // When
        $line = OrderLine::of($product, 3);

        // Then
        self::assertTrue($product->equals($line->product));
        self::assertSame(3, $line->quantity);
    }

    #[Test]
    #[DataProvider('provideInvalidValues')]
    public function itProtectsInvariants(int $quantity): void
    {
        // Given
        $product = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        OrderLine::of($product, $quantity);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'zero quantity' => [0];
        yield 'negative quantity' => [-1];
    }

    #[Test]
    public function itComparesEquality(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $a = OrderLine::of(Product::of($id, Label::fromString('Saucer'), Money::fromCents(83)), 3);
        $b = OrderLine::of(Product::of($id, Label::fromString('  Saucer  '), Money::fromCents(83)), 3);
        $other = OrderLine::of(Product::of($id, Label::fromString('Saucer'), Money::fromCents(83)), 4);

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
        // Given
        $product = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));

        // When
        $total = OrderLine::of($product, 3)->total();

        // Then
        self::assertSame(249, $total->toCents());
    }
}
