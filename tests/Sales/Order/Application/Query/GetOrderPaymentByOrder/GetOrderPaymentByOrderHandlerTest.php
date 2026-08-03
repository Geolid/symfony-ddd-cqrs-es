<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrderPaymentByOrder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Query\GetOrderPaymentByOrder\GetOrderPaymentByOrder;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetOrderPaymentByOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsAPaymentByItsOrder(): void
    {
        // Given
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-9F3K2M1P')->create();
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetOrderPaymentByOrder($orderPayment->orderId()));

        // Then
        self::assertNotNull($result);
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame('GLBX-9F3K2M1P', $result->reference);
    }

    #[Test]
    public function itReturnsNullWhenNoPaymentExistsForTheOrder(): void
    {
        // When
        $result = $this->ask(new GetOrderPaymentByOrder(Uuid::uuid7()->toString()));

        // Then
        self::assertNull($result);
    }
}
