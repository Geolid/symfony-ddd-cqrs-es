<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderConfirmed;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderConfirmedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $order = OrderFactory::new()->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderConfirmedIntegrationEvent::class);
        $shippingAddress = PostalAddressMapper::toArray($order->shippingAddress);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($order->cartId, $event->cartId);
        self::assertSame($order->customerId, $event->customerId);
        self::assertSame($order->checkoutSessionId, $event->checkoutSessionId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($order->confirmedAt->format(\DateTimeInterface::ATOM), $event->confirmedAt->format(\DateTimeInterface::ATOM));
    }
}
