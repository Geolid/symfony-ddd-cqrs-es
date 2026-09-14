<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Mapper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Mapper\OrderItemMapper;
use Shared\Domain\ValueObject\Currency;

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
            'taxAmountInCents' => 500,
        ];

        // When
        $item = OrderItemMapper::fromArray($data, Currency::EUR);

        // Then
        self::assertSame($productId, $item->product->id);
        self::assertSame('Espresso cups, set of 6', $item->product->label->value);
        self::assertSame(2500, $item->product->price->cents);
        self::assertSame(2, $item->quantity->value);
        self::assertSame(500, $item->taxAmount->cents);
    }
}
