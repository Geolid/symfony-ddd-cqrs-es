<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentCaptured;

use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentCapturedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $builder = PaymentBuilder::new()->authorized()->captured($orderId);
        $payment = $builder->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentCapturedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSame($builder['capturedAt']->format(\DateTimeInterface::ATOM), $event->capturedAt->format(\DateTimeInterface::ATOM));
    }
}
