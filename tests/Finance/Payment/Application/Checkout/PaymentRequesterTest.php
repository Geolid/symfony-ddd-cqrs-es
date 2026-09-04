<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Checkout;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Checkout\PaymentRequester;
use Finance\Payment\Application\Checkout\PaymentSession;
use Finance\Payment\Application\Exception\PlacedOrderAlreadyCancelledException;
use Finance\Payment\Application\Exception\PlacedOrderResultNotFoundException;
use Finance\Payment\Application\Finder\PlacedOrder\PlacedOrderFinderInterface;
use Finance\Payment\Application\Query\GetPaymentByReference\GetPaymentByReference;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Command\CommandBusInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRequesterTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private PaymentRequester $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->service = new PaymentRequester(
            $this->service(PaymentRepositoryInterface::class),
            $this->service(PlacedOrderFinderInterface::class),
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
        $reference = PaymentBuilder::sample('reference')->value;
        $checkoutUrl = PaymentBuilder::sample('checkoutUrl');
        $this->paymentGateway->expects(self::once())->method('requestPayment')
            ->with($order->id->toString(), $order->totalAmountInCents, 'https://web.test/sales/orders', $order->billingAddress)
            ->willReturn(new PaymentSession($reference, $checkoutUrl));

        // When
        $result = $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame($checkoutUrl, $result);

        $payment = $this->ask(new GetPaymentByReference($reference));
        self::assertSame($reference, $payment->reference);
        self::assertSame($checkoutUrl, $payment->checkoutUrl);
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $paymentBuilder = PaymentBuilder::new()->withOrderId($order->id->toString());
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
        $this->expectException(PlacedOrderResultNotFoundException::class);

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
        $this->expectException(PlacedOrderAlreadyCancelledException::class);

        // When
        $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');
    }
}
