<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Mapper\OrderItemMapper;

final class OrderItemMapperTest extends TestCase
{
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
        $item = OrderItemMapper::fromArray($data);

        // Then
        self::assertSame($productId, $item->product->id);
        self::assertSame('Espresso cups, set of 6', $item->product->label->value);
        self::assertSame(2500, $item->product->price->cents);
        self::assertSame(2, $item->quantity->value);
    }
}
