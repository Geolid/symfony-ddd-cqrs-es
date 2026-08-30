<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrderPaymentByReference;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderPaymentByReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByReference(): void
    {
        // Given
        $paymentFactory = OrderPaymentTestFactory::new();
        $orderPayment = $paymentFactory->create();
        $orderId = $paymentFactory->attribute('orderId');
        $reference = $paymentFactory->attribute('reference')->value;
        $checkoutUrl = $paymentFactory->attribute('checkoutUrl');
        $amountInCents = $paymentFactory->attribute('amount')->cents;
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetOrderPaymentByReference($reference));

        // Then
        self::assertSame($orderPayment->id->toString(), $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame($amountInCents, $result->amountInCents);
        self::assertSame($reference, $result->reference);
        self::assertSame($checkoutUrl, $result->checkoutUrl);
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
        $this->ask(new GetOrderPaymentByReference('GLBX-NEVER-ISSUED'));
    }
}
