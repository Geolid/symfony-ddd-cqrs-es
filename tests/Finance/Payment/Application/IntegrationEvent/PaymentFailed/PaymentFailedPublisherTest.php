<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentFailed;

use Finance\Payment\Application\IntegrationEvent\PaymentFailed\PaymentFailedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentFailedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $builder = PaymentBuilder::new()->authorized()->failed($orderId);
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentFailedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSame($builder['failedAt']->format(\DateTimeInterface::ATOM), $event->failedAt->format(\DateTimeInterface::ATOM));
    }
}
