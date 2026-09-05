<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Money;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPlacedSerializationTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itSurvivesRealSerialization(): void
    {
        // Given
        $builder = OrderBuilder::new();
        $order = $builder->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderPlaced::class,
            static fn (OrderPlaced $event): bool => $event->id === $order->id->toString(),
        );

        // When
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);

        // Then
        self::assertInstanceOf(OrderPlaced::class, $rehydrated);
        $expectedLines = $builder['lines'];
        $expectedTotal = array_reduce(
            $expectedLines,
            static fn (Money $carry, OrderLine $line): Money => $carry->plus($line->total()),
            Money::fromCents(0),
        );

        self::assertSame($expectedTotal->cents, $rehydrated->totalAmount->cents);

        self::assertCount(\count($expectedLines), $rehydrated->lines);

        foreach ($expectedLines as $i => $expectedLine) {
            $rehydratedLine = $rehydrated->lines[$i];
            self::assertInstanceOf(OrderLine::class, $rehydratedLine);
            self::assertSame($expectedLine->product->id, $rehydratedLine->product->id);
            self::assertSame($expectedLine->product->label->value, $rehydratedLine->product->label->value);
            self::assertSame($expectedLine->product->price->cents, $rehydratedLine->product->price->cents);
            self::assertSame($expectedLine->quantity, $rehydratedLine->quantity);
        }
    }
}
