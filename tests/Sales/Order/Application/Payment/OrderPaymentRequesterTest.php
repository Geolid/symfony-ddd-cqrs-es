<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Payment;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentGatewayStatus;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Application\Query\GetOrderPaymentByReference\GetOrderPaymentByReference;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Tests\Order\Support\Builder\OrderPaymentBuilder;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class OrderPaymentRequesterTest extends AbstractIntegrationTestCase
{
    private SpyPaymentGateway $paymentGateway;

    private OrderPaymentRequester $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = new SpyPaymentGateway();
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
        $order = OrderBuilder::new()->withTotalAmountInCents(4_200)->create();
        $this->store($order);

        // When
        $checkoutUrl = $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame(SpyPaymentGateway::CHECKOUT_URL, $checkoutUrl);
        self::assertSame($order->id->toString(), $this->paymentGateway->orderId);
        self::assertSame(4_200, $this->paymentGateway->amountInCents);
        self::assertSame('https://web.test/sales/orders', $this->paymentGateway->returnUrl);
        self::assertNotNull($this->paymentGateway->billingAddress);
        $billingAddress = $order->billingAddress;
        self::assertSame(
            [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
                'countryCode' => $billingAddress->address->countryCode->value,
            ],
            [
                'firstName' => $this->paymentGateway->billingAddress->fullName->firstName,
                'lastName' => $this->paymentGateway->billingAddress->fullName->lastName,
                'street' => $this->paymentGateway->billingAddress->address->street,
                'postalCode' => $this->paymentGateway->billingAddress->address->postalCode,
                'city' => $this->paymentGateway->billingAddress->address->city,
                'countryCode' => $this->paymentGateway->billingAddress->address->countryCode->value,
            ],
        );

        $orderPayment = $this->ask(new GetOrderPaymentByReference(SpyPaymentGateway::CHARGE_REFERENCE));
        self::assertSame(SpyPaymentGateway::CHARGE_REFERENCE, $orderPayment->reference);
        self::assertSame(SpyPaymentGateway::CHECKOUT_URL, $orderPayment->checkoutUrl);
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyRequested(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $payment = OrderPaymentBuilder::new()->withOrderId($order->id->toString())->withCheckoutUrl('https://checkout.globex.test/pay/existing')->create();
        $this->store($order, $payment);

        // When
        $checkoutUrl = $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');

        // Then
        self::assertSame('https://checkout.globex.test/pay/existing', $checkoutUrl);
        self::assertNull($this->paymentGateway->orderId);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
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

        // Then
        $this->expectException(OrderAlreadyCancelledException::class);

        // When
        $this->service->requestFor($order->id->toString(), 'https://web.test/sales/orders');
    }
}

final class SpyPaymentGateway implements PaymentGatewayInterface
{
    public const string CHARGE_REFERENCE = 'GLBX-9F3K2M1P';

    public const string CHECKOUT_URL = 'https://checkout.globex.test/pay/GLBX-9F3K2M1P';

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

    public function checkStatus(string $reference): PaymentGatewayStatus
    {
        throw new \LogicException('Not needed by this test.');
    }
}
