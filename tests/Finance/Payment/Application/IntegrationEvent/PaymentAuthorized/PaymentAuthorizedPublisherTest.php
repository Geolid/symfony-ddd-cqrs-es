<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\IntegrationEvent\PaymentAuthorized;

use Finance\Payment\Application\IntegrationEvent\PaymentAuthorized\PaymentAuthorizedIntegrationEvent;
use Finance\Tests\Payment\Support\Factory\PaymentFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentAuthorizedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $payment = PaymentFactory::new()->authorized()->create();

        // When
        $this->store($payment);

        // Then
        $event = $this->publishedEventOf(PaymentAuthorizedIntegrationEvent::class);
        self::assertSame($payment->id->toString(), $event->paymentId);
        self::assertSame($payment->checkoutSessionId, $event->checkoutSessionId);
        self::assertSameDate($payment->authorizedAt, $event->authorizedAt);
    }
}
