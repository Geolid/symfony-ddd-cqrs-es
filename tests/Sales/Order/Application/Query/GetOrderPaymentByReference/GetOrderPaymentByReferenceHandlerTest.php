<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrderPaymentByReference;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderPaymentStatus;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderPaymentByReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAPaymentByItsReference(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $orderPayment = OrderPaymentTestFactory::new()
            ->withOrderId($orderId)
            ->withReference('GLBX-9F3K2M1P')
            ->withAmountInCents(4_200)
            ->withCheckoutUrl('https://fake-checkout.test/?ref=GLBX-9F3K2M1P')
            ->store();

        // When
        $result = $this->ask(new GetOrderPaymentByReference('GLBX-9F3K2M1P'));

        // Then
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(4_200, $result->amountInCents);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
        self::assertSame('https://fake-checkout.test/?ref=GLBX-9F3K2M1P', $result->checkoutUrl);
        self::assertSame(OrderPaymentStatus::REQUESTED, $result->status);
        self::assertNotNull($result->requestedAt);
        self::assertNull($result->capturedAt);
    }

    #[Test]
    public function itFailsWhenNoPaymentCarriesThatReference(): void
    {
        // Then
        $this->expectException(OrderPaymentResultNotFoundException::class);

        // When
        $this->ask(new GetOrderPaymentByReference('GLBX-NEVER-ISSUED'));
    }
}
