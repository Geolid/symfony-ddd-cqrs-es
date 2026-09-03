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
        $id = Uuid::uuid7()->toString();
        $product = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));

        // When
        $line = OrderLine::of($product, 3);

        // Then
        self::assertSame($id, $line->product->id);
        self::assertSame('Saucer', $line->product->label->value);
        self::assertSame(83, $line->product->price->cents);
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
    public function itEquals(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $a = OrderLine::of(Product::of($id, Label::fromString('Saucer'), Money::fromCents(83)), 3);
        $b = OrderLine::of(Product::of($id, Label::fromString('  Saucer  '), Money::fromCents(83)), 3);

        // When
        $equals = $a->equals($b);

        // Then
        self::assertTrue($equals);
    }

    #[Test]
    public function itDiffers(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $a = OrderLine::of(Product::of($id, Label::fromString('Saucer'), Money::fromCents(83)), 3);

        $differentProduct = OrderLine::of(Product::of($id, Label::fromString('Plate'), Money::fromCents(83)), 3);
        $differentQuantity = OrderLine::of(Product::of($id, Label::fromString('Saucer'), Money::fromCents(83)), 4);

        // When
        $differsOnProduct = $a->equals($differentProduct);
        $differsOnQuantity = $a->equals($differentQuantity);

        // Then
        self::assertFalse($differsOnProduct);
        self::assertFalse($differsOnQuantity);
    }

    #[Test]
    public function itTotalsUnitAmountOverQuantity(): void
    {
        // Given
        $product = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));

        // When
        $total = OrderLine::of($product, 3)->total();

        // Then
        self::assertSame(249, $total->cents);
    }
}
