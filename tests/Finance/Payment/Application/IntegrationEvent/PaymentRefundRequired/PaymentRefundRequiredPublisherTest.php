<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentRefundRequired;

use Finance\Payment\Application\IntegrationEvent\PaymentRefundRequired\PaymentRefundRequiredIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRefundRequiredPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new()->authorized()->captured()->cancelled();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentRefundRequiredIntegrationEvent::class);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['cancelledAt']->format(\DateTimeInterface::ATOM), $event->requiredAt->format(\DateTimeInterface::ATOM));
    }
}
