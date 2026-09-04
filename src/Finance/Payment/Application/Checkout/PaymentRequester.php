<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Exception\PlacedOrderAlreadyCancelledException;
use Finance\Payment\Application\Exception\PlacedOrderResultNotFoundException;
use Finance\Payment\Application\Finder\PlacedOrder\PlacedOrderFinderInterface;
use Finance\Payment\Domain\Repository\PaymentRepositoryInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class PaymentRequester implements PaymentRequesterInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private PlacedOrderFinderInterface $orderFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws PlacedOrderResultNotFoundException
     * @throws PlacedOrderAlreadyCancelledException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $orderId, string $returnUrl): string
    {
        $order = $this->orderFinder->ofId($orderId);

        if ($order->cancelled) {
            throw PlacedOrderAlreadyCancelledException::forOrder($orderId);
        }

        $paymentId = PaymentId::forOrder($orderId);

        if ($this->paymentRepository->has($paymentId)) {
            return $this->paymentRepository->load($paymentId)->checkoutUrl;
        }

        $billingAddress = PostalAddress::of(
            $order->billingAddress->recipientName,
            Address::of($order->billingAddress->street, $order->billingAddress->postalCode, $order->billingAddress->city, $order->billingAddress->countryCode),
        );

        $session = $this->paymentGateway->requestPayment($orderId, $order->amountInCents, $returnUrl, $billingAddress);

        $this->commandBus->dispatch(new RequestPayment(
            id: $paymentId->toString(),
            orderId: $orderId,
            amountInCents: $order->amountInCents,
            reference: $session->reference,
            checkoutUrl: $session->checkoutUrl,
        ));

        return $session->checkoutUrl;
    }
}
