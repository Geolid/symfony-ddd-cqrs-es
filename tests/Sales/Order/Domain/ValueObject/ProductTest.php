<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Domain\ValueObject;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\Product;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;

final class ProductTest extends TestCase
{
    #[Test]
    public function itCreates(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $label = Label::fromString('Saucer');

        // When
        $product = Product::of($id, $label, Money::fromCents(1_750));

        // Then
        self::assertSame($id, $product->id);
        self::assertSame('Saucer', $product->label->value);
        self::assertSame(1_750, $product->price->cents);
    }

    #[Test]
    public function itProtectsInvariants(): void
    {
        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        Product::of('', Label::fromString('Saucer'), Money::fromCents(1_750));
    }

    #[Test]
    public function itEquals(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $a = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));
        $b = Product::of($id, Label::fromString('  Saucer  '), Money::fromCents(83));

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
        $a = Product::of($id, Label::fromString('Saucer'), Money::fromCents(83));

        $differentId = Product::of(Uuid::uuid7()->toString(), Label::fromString('Saucer'), Money::fromCents(83));
        $differentLabel = Product::of($id, Label::fromString('Plate'), Money::fromCents(83));
        $differentPrice = Product::of($id, Label::fromString('Saucer'), Money::fromCents(90));

        // When
        $differsOnId = $a->equals($differentId);
        $differsOnLabel = $a->equals($differentLabel);
        $differsOnPrice = $a->equals($differentPrice);

        // Then
        self::assertFalse($differsOnId);
        self::assertFalse($differsOnLabel);
        self::assertFalse($differsOnPrice);
    }
}
