<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentAbandoned;

use Finance\Payment\Application\IntegrationEvent\PaymentAbandoned\PaymentAbandonedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentAbandonedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = PaymentBuilder::new()->abandoned();
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentAbandonedIntegrationEvent::class);
        self::assertSame($payment->id->toString(), $event->paymentId);
        self::assertSame($builder['cartId'], $event->cartId);
        self::assertSame($builder['abandonedAt']->format(\DateTimeInterface::ATOM), $event->abandonedAt->format(\DateTimeInterface::ATOM));
    }
}
