<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPlaced;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderPlaced\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class OrderPlacedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $productId = Uuid::uuid7()->toString();
        $now = Clock::get()->now();
        $order = OrderTestFactory::new()
            ->withCustomerId($customerId)
            ->withLines([OrderLine::of(Product::of($productId, Label::fromString('Assorted goods'), Money::fromCents(2_500)), 1)])
            ->withPlacedAt($now)
            ->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderPlacedIntegrationEvent::class);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($customerId, $event->customerId);
        self::assertSame(
            [['productId' => $productId, 'label' => 'Assorted goods', 'quantity' => 1, 'unitAmountInCents' => 2_500]],
            $event->lines,
        );
        self::assertSame(2_500, $event->totalAmountInCents);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->placedAt->format(\DateTimeImmutable::ATOM));
    }
}
