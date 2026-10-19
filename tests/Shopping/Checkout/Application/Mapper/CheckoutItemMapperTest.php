<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Tests\Checkout\Support\Factory\CheckoutItemFactory;

final class CheckoutItemMapperTest extends TestCase
{
    #[Test]
    public function itMapsToArray(): void
    {
        // Given
        $item = CheckoutItemFactory::new()->create();

        // When
        $array = CheckoutItemMapper::toArray($item);

        // Then
        self::assertSame(
            [
                'productId' => $item->productId,
                'label' => $item->label->value,
                'unitPriceInCents' => $item->unitPrice->cents,
                'quantity' => $item->quantity->value,
            ],
            $array,
        );
    }

    #[Test]
    public function itMapsFromArray(): void
    {
        // Given
        $source = CheckoutItemFactory::new()->create();
        $data = [
            'productId' => $source->productId,
            'label' => $source->label->value,
            'unitPriceInCents' => $source->unitPrice->cents,
            'quantity' => $source->quantity->value,
        ];

        // When
        $item = CheckoutItemMapper::fromArray($data, $source->unitPrice->currency, $source->taxRate);

        // Then
        self::assertSame($source->productId, $item->productId);
        self::assertSame($source->label->value, $item->label->value);
        self::assertSame($source->unitPrice->cents, $item->unitPrice->cents);
        self::assertSame($source->quantity->value, $item->quantity->value);
    }
}
