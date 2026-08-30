<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\FailOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\FailOrderPayment\FailOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class FailOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFailsWhenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $paymentFactory = OrderPaymentTestFactory::new()->withOrderId($order->id->toString());
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new FailOrderPayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference($paymentFactory->attribute('reference')->value);
        self::assertSame(OrderPaymentStatus::FAILED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAuthorized(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();
        $orderPayment = OrderPaymentTestFactory::new()->withOrderId($order->id->toString())->authorized()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new FailOrderPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentTestFactory::new()->create()->id->toString();

        // Then
        $this->expectException(OrderPaymentNotFoundException::class);

        // When
        $this->dispatch(new FailOrderPayment($id));
    }
}
