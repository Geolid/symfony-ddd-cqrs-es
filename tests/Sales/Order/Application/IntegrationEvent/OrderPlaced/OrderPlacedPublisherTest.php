<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPlaced;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPlacedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderPlacedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['buyerId'], $event->buyerId);
        self::assertSame($this->primitiveLines($builder['lines']), $event->lines);
        self::assertSame($this->totalAmountInCents($builder['lines']), $event->totalAmountInCents);
        self::assertSame($order->billingAddress->toArray(), $event->billingAddress);
        self::assertSame($builder['placedAt']->format(\DateTimeInterface::ATOM), $event->placedAt->format(\DateTimeInterface::ATOM));
    }

    /**
     * @param list<OrderLine> $lines
     */
    private function totalAmountInCents(array $lines): int
    {
        return array_sum(array_map(static fn (OrderLine $line): int => $line->total()->cents, $lines));
    }

    /**
     * @param list<OrderLine> $lines
     *
     * @return list<array{productId: string, label: string, quantity: int, unitPriceInCents: int}>
     */
    private function primitiveLines(array $lines): array
    {
        return array_map(
            static fn (OrderLine $line): array => [
                'productId' => $line->product->id,
                'label' => $line->product->label->value,
                'quantity' => $line->quantity,
                'unitPriceInCents' => $line->product->price->cents,
            ],
            $lines,
        );
    }
}
