<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\IntegrationEvent\OrderPaymentRequested;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\IntegrationEvent\OrderPaymentRequested\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class OrderPaymentRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = OrderId::generate()->toString();
        $now = Clock::get()->now();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withAmountInCents(2_500)
            ->withReference('GLBX-ABC12345')
            ->withCheckoutUrl('https://checkout.globex.test/pay/GLBX-ABC12345')
            ->withRequestedAt($now)
            ->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(OrderPaymentRequestedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSame(2_500, $event->amountInCents);
        self::assertSame('GLBX-ABC12345', $event->reference);
        self::assertSame('https://checkout.globex.test/pay/GLBX-ABC12345', $event->checkoutUrl);
        self::assertSame($now->format(\DateTimeImmutable::ATOM), $event->requestedAt->format(\DateTimeImmutable::ATOM));
    }
}
