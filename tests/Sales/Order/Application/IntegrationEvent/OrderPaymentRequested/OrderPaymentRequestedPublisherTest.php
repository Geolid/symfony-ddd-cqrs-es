<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPaymentRequested;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderPaymentRequested\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = OrderId::generate()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-ABC12345')
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(OrderPaymentRequestedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSame(2_500, $event->amountInCents);
        self::assertSame('GLBX-ABC12345', $event->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-ABC12345', $event->checkoutUrl);
    }
}
