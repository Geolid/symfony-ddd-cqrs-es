<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Query\GetOrder\GetOrder;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAnOrderById(): void
    {
        // Given
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->withTotalAmountInCents(2_500)->create();
        $this->store($order);
        $this->store(OrderTestFactory::new()->create());

        // When
        $result = $this->ask(new GetOrder($order->id()->toString()));

        // Then
        self::assertSame($order->id()->toString(), $result->id);
        self::assertSame('customer-1', $result->customerId);
        self::assertSame(2_500, $result->totalAmountInCents);
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->ask(new GetOrder(OrderId::generate()->toString()));
    }
}
