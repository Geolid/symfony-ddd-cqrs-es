<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\CancelOrderPayment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\CancelOrderPayment\CancelOrderPayment;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\OrderPaymentStatus;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelOrderPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsWhenRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->withReference('GLBX-9F3K2M1P')->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CancelOrderPayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(OrderPaymentFinderInterface::class)->ofReference('GLBX-9F3K2M1P');
        self::assertSame(OrderPaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenFailed(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->failed()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CancelOrderPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $id = OrderPaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // When
        $this->dispatch(new CancelOrderPayment($id));

        // Then
        self::expectNotToPerformAssertions();
    }
}
