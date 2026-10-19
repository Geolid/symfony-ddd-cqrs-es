<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sales\Ordering\Application\Mapper\OrderItemMapper;
use Sales\Tests\Ordering\Support\Factory\OrderItemFactory;

final class OrderItemMapperTest extends TestCase
{
    #[Test]
    public function itMapsFromArray(): void
    {
        // Given
        $source = OrderItemFactory::new()->create();
        $data = [
            'productId' => $source->product->id,
            'label' => $source->product->label->value,
            'unitPriceInCents' => $source->product->price->cents,
            'quantity' => $source->quantity->value,
            'taxAmountInCents' => $source->taxAmount->cents,
        ];

        // When
        $item = OrderItemMapper::fromArray($data, $source->product->price->currency);

        // Then
        self::assertSame($source->product->id, $item->product->id);
        self::assertSame($source->product->label->value, $item->product->label->value);
        self::assertSame($source->product->price->cents, $item->product->price->cents);
        self::assertSame($source->quantity->value, $item->quantity->value);
        self::assertSame($source->taxAmount->cents, $item->taxAmount->cents);
    }
}
