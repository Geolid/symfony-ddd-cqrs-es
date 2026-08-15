<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Query\GetOrder\GetOrder;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAnOrderById(): void
    {
        // Given
        $order = OrderTestFactory::new()->withTotalAmountInCents(4_200)->store();

        // When
        $result = $this->ask(new GetOrder($order->id()->toString()));

        // Then
        self::assertSame($order->id()->toString(), $result->id);
        self::assertSame($order->customerId(), $result->customerId);
        self::assertSame(4_200, $result->totalAmountInCents);
        self::assertSame(OrderStatus::PLACED, $result->status);
    }

    #[Test]
    public function itFailsWhenNoOrderCarriesThatId(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->ask(new GetOrder(Uuid::uuid7()->toString()));
    }
}
