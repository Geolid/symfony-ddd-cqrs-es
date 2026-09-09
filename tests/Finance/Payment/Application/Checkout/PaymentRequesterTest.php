<?php

declare(strict_types=1);

namespace Finance\Tests\Payment\Application\Checkout;

use Finance\Payment\Application\Checkout\PaymentRequester;
use Finance\Payment\Application\Checkout\PaymentSession;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\ValueObject\PaymentUniqueKey;
use Finance\Tests\Payment\Support\Builder\PaymentBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PaymentRequesterTest extends AbstractIntegrationTestCase
{
    private PaymentGatewayInterface&MockObject $paymentGateway;

    private PaymentRequester $service;
    private PostalAddress $billingAddress;
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentGateway = $this->createMock(PaymentGatewayInterface::class);
        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
        $this->service = new PaymentRequester(
            $this->uniqueValues,
            $this->service(PaymentFinderInterface::class),
            $this->paymentGateway,
            $this->service(CommandBusInterface::class),
        );
        $this->billingAddress = PostalAddress::of('Jane Doe', Address::of('1 Main St', '75001', 'Paris', 'FR'));
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $cartId = Uuid::uuid7()->toString();
        $reference = PaymentBuilder::sample('reference')->value;
        $checkoutUrl = PaymentBuilder::sample('checkoutUrl');
        $this->paymentGateway->expects(self::once())->method('requestPayment')
            ->with($cartId, 4_200, 'https://web.test/sales/orders', $this->billingAddress)
            ->willReturn(new PaymentSession($reference, $checkoutUrl));

        // When
        $result = $this->service->requestFor($cartId, 4_200, $this->billingAddress, 'https://web.test/sales/orders');

        // Then
        self::assertSame($checkoutUrl, $result);
    }

    #[Test]
    public function itReturnsExistingWhenAlreadyRequested(): void
    {
        // Given
        $paymentBuilder = PaymentBuilder::new();
        $payment = $paymentBuilder->create();
        $this->store($payment);
        $this->uniqueValues->reserve(
            UniqueKey::for(PaymentUniqueKey::CART),
            $paymentBuilder['cartId'],
            $payment->id->toString(),
        );
        $this->paymentGateway->expects(self::never())->method('requestPayment');

        // When
        $checkoutUrl = $this->service->requestFor($paymentBuilder['cartId'], 4_200, $this->billingAddress, 'https://web.test/sales/orders');

        // Then
        self::assertSame($paymentBuilder['checkoutUrl'], $checkoutUrl);
    }
}
