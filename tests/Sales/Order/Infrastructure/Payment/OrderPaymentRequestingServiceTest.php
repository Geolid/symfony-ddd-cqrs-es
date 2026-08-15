<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Payment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Infrastructure\Payment\OrderPaymentRequestingService;
use Sales\Tests\Order\Support\Factory\OrderPaymentTestFactory;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentRequestingServiceTest extends AbstractIntegrationTestCase
{
    private DummyPaymentGateway $paymentGateway;

    private OrderPaymentRequestingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new DummyPaymentGateway();
        $this->service = new OrderPaymentRequestingService(
            $this->service(OrderFinderInterface::class),
            $this->service(OrderPaymentFinderInterface::class),
            $this->service(OrderRepositoryInterface::class),
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
        );
    }

    #[Test]
    public function itRequestsPaymentForAPlacedOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->withTotalAmountInCents(4_200)->store();

        // When
        $checkoutUrl = $this->service->requestFor($order->id()->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame(DummyPaymentGateway::CHECKOUT_URL, $checkoutUrl);
        self::assertSame($order->id()->toString(), $this->paymentGateway->orderId);
        self::assertSame(4_200, $this->paymentGateway->amountInCents);
        self::assertSame('https://web.test/sales/orders', $this->paymentGateway->returnUrl);
        self::assertNotNull($this->paymentGateway->billingAddress);
        self::assertTrue($order->billingAddress()->equals($this->paymentGateway->billingAddress));

        $orderPayment = $this->service(OrderPaymentFinderInterface::class)->ofOrderOrNull($order->id()->toString());
        self::assertNotNull($orderPayment);
        self::assertSame(DummyPaymentGateway::CHARGE_REFERENCE, $orderPayment->reference);
        self::assertSame(DummyPaymentGateway::CHECKOUT_URL, $orderPayment->checkoutUrl);
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->service->requestFor(Uuid::uuid7()->toString(), 'https://web.test/sales/orders');
    }

    #[Test]
    public function itFailsWhenAPaymentHasAlreadyBeenRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        OrderPaymentTestFactory::new()->withOrderId($order->id()->toString())->store();

        // Then
        $this->expectException(OrderPaymentAlreadyRequestedException::class);

        // When
        $this->service->requestFor($order->id()->toString(), 'https://web.test/sales/orders');
    }

    #[Test]
    public function itFailsWhenTheOrderIsCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->store();

        // Then
        $this->expectException(OrderAlreadyCancelledException::class);

        // When
        $this->service->requestFor($order->id()->toString(), 'https://web.test/sales/orders');
    }
}

final class DummyPaymentGateway implements PaymentGatewayInterface
{
    public const string CHARGE_REFERENCE = 'GLBX-9F3K2M1P';

    public const string CHECKOUT_URL = 'https://fake-checkout.test/?ref=GLBX-9F3K2M1P';

    public ?string $orderId = null;

    public ?int $amountInCents = null;

    public ?string $returnUrl = null;

    public ?PostalAddress $billingAddress = null;

    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        $this->orderId = $orderId;
        $this->amountInCents = $amountInCents;
        $this->returnUrl = $returnUrl;
        $this->billingAddress = $billingAddress;

        return new PaymentSession(self::CHARGE_REFERENCE, self::CHECKOUT_URL);
    }

    public function void(string $reference): void
    {
    }

    public function refund(string $reference): void
    {
    }
}
