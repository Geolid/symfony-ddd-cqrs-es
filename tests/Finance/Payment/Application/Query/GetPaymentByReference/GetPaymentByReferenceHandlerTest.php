<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Query\GetPaymentByReference;

use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Application\Query\GetPaymentByReference\GetPaymentByReference;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetPaymentByReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $paymentFactory = PaymentBuilder::new();
        $orderPayment = $paymentFactory->create();
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetPaymentByReference($paymentFactory['reference']->value));

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($paymentFactory['orderId'], $result->orderId);
        self::assertSame($paymentFactory['amount']->cents, $result->amountInCents);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
        self::assertSame($paymentFactory['checkoutUrl'], $result->checkoutUrl);
        self::assertSame(PaymentStatus::REQUESTED, $result->status);
        self::assertNotNull($result->requestedAt);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(PaymentResultNotFoundException::class);

        // When
        $this->ask(new GetPaymentByReference(PaymentBuilder::sample('reference')->value));
    }
}
