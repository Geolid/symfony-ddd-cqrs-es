<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPaymentRequesterTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private OrderPaymentRequester $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->service = new OrderPaymentRequester(
            $this->service(OrderPaymentRepositoryInterface::class),
            $this->service(OrderRepositoryInterface::class),
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itRequestsWhenPlaced(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);
        $reference = OrderPaymentBuilder::sample('reference')->value;
        $checkoutUrl = OrderPaymentBuilder::sample('checkoutUrl');
        $this->paymentGateway->expects(self::once())->method('requestPayment')
            ->with($order->id->toString(), $order->totalAmountInCents, 'https://web.test/sales/orders', $order->billingAddress)
            ->willReturn(new PaymentSession($reference, $checkoutUrl));

        // When
        $result = $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame($checkoutUrl, $result);

        $orderPayment = $this->ask(new GetOrderPaymentByReference($reference));
        self::assertSame($reference, $orderPayment->reference);
        self::assertSame($checkoutUrl, $orderPayment->checkoutUrl);
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = OrderPaymentBuilder::new()->withOrderId($order->id->toString());
        $payment = $paymentBuilder->create();
        $this->store($order, $payment);
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // When
        $checkoutUrl = $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame($paymentBuilder['checkoutUrl'], $checkoutUrl);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'https://web.test/sales/orders');
    }

    #[Test]
    public function itFailsWhenCancelled(): void
    {
        // Given
        $order = OrderBuilder::new()->cancelled()->create();
        $this->store($order);
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // Then
        $this->expectException(OrderAlreadyCancelledException::class);

        // When
        $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');
    }
}
