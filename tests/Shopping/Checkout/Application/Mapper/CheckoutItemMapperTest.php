<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Currency;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Quantity;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;
use Shopping\Checkout\Domain\ValueObject\TaxRate;

final class CheckoutItemMapperTest extends TestCase
{
    #[Test]
    public function itMapsToArray(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $item = CheckoutItem::of($productId, Label::fromString('Espresso cups, set of 6'), Money::fromCents(2500, 'EUR'), Quantity::of(2), TaxRate::fromBasisPoints(2000));

        // When
        $array = CheckoutItemMapper::toArray($item);

        // Then
        self::assertSame(
            [
                'productId' => $productId,
                'label' => 'Espresso cups, set of 6',
                'unitPriceInCents' => 2500,
                'quantity' => 2,
            ],
            $array,
        );
    }

    #[Test]
    public function itMapsFromArray(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $data = [
            'productId' => $productId,
            'label' => 'Espresso cups, set of 6',
            'unitPriceInCents' => 2500,
            'quantity' => 2,
        ];

        // When
        $item = CheckoutItemMapper::fromArray($data, Currency::EUR, TaxRate::fromBasisPoints(2000));

        // Then
        self::assertSame($productId, $item->productId);
        self::assertSame('Espresso cups, set of 6', $item->label->value);
        self::assertSame(2500, $item->unitPrice->cents);
        self::assertSame(2, $item->quantity->value);
    }
}
