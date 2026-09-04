<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Command\CancelPayment;

use Finance\Payment\Application\Command\CancelPayment\CancelPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentStatus;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelPaymentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsWhenRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentFactory = PaymentBuilder::new()->withOrderId($order->id->toString());
        $orderPayment = $paymentFactory->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CancelPayment($orderPayment->id->toString()));

        // Then
        $result = $this->service(PaymentFinderInterface::class)->ofReference($paymentFactory['reference']->value);
        self::assertSame(PaymentStatus::CANCELLED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenFailed(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $orderPayment = PaymentBuilder::new()->withOrderId($order->id->toString())->failed()->create();
        $this->store($order, $orderPayment);

        // When
        $this->dispatch(new CancelPayment($orderPayment->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $id = PaymentId::forOrder(Uuid::uuid7()->toString())->toString();

        // When
        $this->dispatch(new CancelPayment($id));

        // Then
        self::expectNotToPerformAssertions();
    }
}
