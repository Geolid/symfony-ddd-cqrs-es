<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrderPaymentByReference;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetOrderPaymentByReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $paymentFactory = OrderPaymentBuilder::new();
        $orderPayment = $paymentFactory->create();
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetOrderPaymentByReference($paymentFactory['reference']->value));

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($paymentFactory['orderId'], $result->orderId);
        self::assertSame($paymentFactory['amount']->cents, $result->amountInCents);
        self::assertSame($paymentFactory['reference']->value, $result->reference);
        self::assertSame($paymentFactory['checkoutUrl'], $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
        self::assertNotNull($result->requestedAt);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->ask(new GetOrderPaymentByReference(OrderPaymentBuilder::sample('reference')->value));
    }
}
