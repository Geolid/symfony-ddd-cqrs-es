<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Application\Query\GetOrderSummary;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\OrderSummaryStatus;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetOrderSummaryHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->withTotalAmountInCents(4_200)->create();
        $this->store($order);

        // When
        $result = $this->ask(new GetOrderSummary($order->id->toString()));

        // Then
        self::assertSame($order->id->toString(), $result->orderId);
        self::assertSame($buyerId, $result->buyerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame(OrderSummaryStatus::PLACED, $result->status);
        self::assertNotNull($result->placedAt);
        self::assertNull($result->cancelledAt);
        self::assertNull($result->paymentAmountInCents);
        self::assertNull($result->paymentReference);
        self::assertNull($result->paymentCheckoutUrl);
        self::assertNull($result->paidAt);
        self::assertNull($result->trackingNumber);
        self::assertNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate()->toString();

        // Then
        $this->expectException(OrderSummaryResultNotFoundException::class);

        // When
        $this->ask(new GetOrderSummary($id));
    }
}
