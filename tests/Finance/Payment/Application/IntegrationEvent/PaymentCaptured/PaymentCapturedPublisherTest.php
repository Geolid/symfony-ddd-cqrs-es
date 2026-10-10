<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentCaptured;

use Finance\Payment\Application\IntegrationEvent\PaymentCaptured\PaymentCapturedIntegrationEvent;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
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
        $payment = PaymentFactory::new()->authorized()->captured($orderId)->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentCapturedIntegrationEvent::class);
        self::assertSame($orderId, $event->orderId);
        self::assertSameDate($payment->capturedAt, $event->capturedAt);
    }
}
