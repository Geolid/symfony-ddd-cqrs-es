<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentRefundFailed;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundFailed\PaymentRefundFailedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRefundFailedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new()->authorized()->captured()->refundRequested()->refundFailed();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentRefundFailedIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['refundId'], $event->refundId);
        self::assertSame($builder['refundFailedAt']->format(\DateTimeInterface::ATOM), $event->failedAt->format(\DateTimeInterface::ATOM));
    }
}
