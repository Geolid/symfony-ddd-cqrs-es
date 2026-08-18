<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\GetOrderSummary;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderSummaryHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAnOrderSummary(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(4_200)->store();

        // When
        $result = $this->ask(new GetOrderSummary($order->id->toString()));

        // Then
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($customerId, $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame('placed', $result->status->value);
        self::assertNotNull($result->placedAt);
        self::assertNull($result->cancelledAt);
        self::assertNull($result->paymentAmountInCents);
        self::assertNull($result->paymentReference);
        self::assertNull($result->paymentCheckoutUrl);
        self::assertNull($result->paidAt);
        self::assertNull($result->trackingReference);
        self::assertNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFailsWhenTheOrderSummaryDoesNotExist(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderSummaryResultNotFoundException::class);

        // When
        $this->ask(new GetOrderSummary($id));
    }
}
