<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\GetOrderSummary;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderSummaryHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAnOrderSummary(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->withTotalAmountInCents(4_200)->create();
        $this->store($order);

        // When
        $result = $this->ask(new GetOrderSummary($order->id()->toString()));

        // Then
        self::assertNotNull($result);
        self::assertSame($order->id()->toString(), $result->orderId);
        self::assertSame('customer-1', $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame('placed', $result->status->value);
        self::assertNull($result->paymentStatus);
        self::assertNull($result->shipmentStatus);
    }

    #[Test]
    public function itGetsNothingWhenTheOrderSummaryDoesNotExist(): void
    {
        // When
        $result = $this->ask(new GetOrderSummary(OrderId::generate()->toString()));

        // Then
        self::assertNull($result);
    }
}
