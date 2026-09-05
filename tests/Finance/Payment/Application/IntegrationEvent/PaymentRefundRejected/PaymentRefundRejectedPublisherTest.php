<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentRefundRejected;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected\PaymentRefundRejectedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRefundRejectedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new()->authorized()->captured()->refundRejected();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentRefundRejectedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['rejectedAt']->format(\DateTimeInterface::ATOM), $event->rejectedAt->format(\DateTimeInterface::ATOM));
    }
}
