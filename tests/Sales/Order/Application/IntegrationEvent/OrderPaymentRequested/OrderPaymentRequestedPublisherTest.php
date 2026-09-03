<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPaymentRequested;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderPaymentRequested\OrderPaymentRequestedIntegrationEvent;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPaymentRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = OrderPaymentBuilder::new();
        $orderPayment = $builder->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(OrderPaymentRequestedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['amount']->cents, $event->amountInCents);
        self::assertSame($builder['reference']->value, $event->reference);
        self::assertSame($builder['checkoutUrl'], $event->checkoutUrl);
        self::assertSame($builder['requestedAt']->format(\DateTimeInterface::ATOM), $event->requestedAt->format(\DateTimeInterface::ATOM));
    }
}
