<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment;

use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Exception\ResultNotFoundException;

final readonly class OrderPaymentRequestingService implements OrderPaymentRequesterInterface
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private OrderPaymentFinderInterface $orderPaymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ResultNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $orderId, string $returnUrl): string
    {
        $result = $this->orderFinder->ofId($orderId);

        if ($result->status->isCancelled()) {
            throw OrderAlreadyCancelledException::forId(OrderId::fromString($orderId));
        }

        if (null !== $this->orderPaymentFinder->ofOrder($orderId)) {
            throw OrderPaymentAlreadyRequestedException::forOrderId($orderId);
        }

        $session = $this->paymentGateway->requestPayment($orderId, $result->totalAmountInCents, $returnUrl);

        $this->commandBus->dispatch(new RequestOrderPayment(
            id: OrderPaymentId::forOrder($orderId)->toString(),
            orderId: $orderId,
            amountInCents: $result->totalAmountInCents,
            reference: $session->reference,
            checkoutUrl: $session->checkoutUrl,
        ));

        return $session->checkoutUrl;
    }
}
