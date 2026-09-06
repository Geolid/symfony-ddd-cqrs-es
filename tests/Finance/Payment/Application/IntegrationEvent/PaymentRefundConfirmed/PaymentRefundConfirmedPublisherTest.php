<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentRefundConfirmed;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundConfirmed\PaymentRefundConfirmedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRefundConfirmedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new()->authorized()->captured()->refundRequested()->refundConfirmed();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentRefundConfirmedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['refundId'], $event->refundId);
        self::assertSame($builder['confirmedAt']->format(\DateTimeInterface::ATOM), $event->confirmedAt->format(\DateTimeInterface::ATOM));
    }
}
