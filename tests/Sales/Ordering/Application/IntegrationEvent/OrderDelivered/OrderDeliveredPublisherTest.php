<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\IntegrationEvent\OrderDelivered;

use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderDelivered\OrderDeliveredIntegrationEvent;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderDeliveredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderBuilder::new()->prepared()->dispatched()->delivered();
        $order = $builder->create();

        // When
        $this->store($order);

        // Then
        $event = $this->publishedEventOf(OrderDeliveredIntegrationEvent::class);
        $shippingAddress = PostalAddressMapper::toArray($order->shippingAddress);
        self::assertSame($order->id->toString(), $event->orderId);
        self::assertSame($builder['shopperId'], $event->shopperId);
        self::assertSame($shippingAddress, $event->shippingAddress);
        self::assertSame($builder['deliveredAt']->format(\DateTimeInterface::ATOM), $event->deliveredAt->format(\DateTimeInterface::ATOM));
    }
}
