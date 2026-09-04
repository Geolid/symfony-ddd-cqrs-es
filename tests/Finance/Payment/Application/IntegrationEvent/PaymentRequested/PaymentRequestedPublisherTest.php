<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentRequested;

use Finance\Payment\Application\IntegrationEvent\PaymentRequested\PaymentRequestedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRequestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new();
        $orderPayment = $builder->create();

        // When
        $this->store($orderPayment);

        // Then
        $event = $this->publishedEventOf(PaymentRequestedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['amount']->cents, $event->amountInCents);
        self::assertSame($builder['reference']->value, $event->reference);
        self::assertSame($builder['checkoutUrl'], $event->checkoutUrl);
        self::assertSame($builder['requestedAt']->format(\DateTimeInterface::ATOM), $event->requestedAt->format(\DateTimeInterface::ATOM));
    }
}
