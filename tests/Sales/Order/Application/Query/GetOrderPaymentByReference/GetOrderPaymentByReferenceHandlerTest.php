<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\GetOrderPaymentByReference;

use PHPUnit\Framework\Attributes\Test;
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
        $orderPayment = OrderPaymentTestFactory::new()->withReference('GLBX-9F3K2M1P')->create();
        $this->store($orderPayment);

        // When
        $result = $this->ask(new GetOrderPaymentByReference('GLBX-9F3K2M1P'));

        // Then
        self::assertSame($orderPayment->id()->toString(), $result->id);
        self::assertSame($orderPayment->orderId(), $result->orderId);
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
