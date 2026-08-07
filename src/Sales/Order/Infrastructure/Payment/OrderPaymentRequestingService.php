<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment;

use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Gateway\PaymentGatewayInterface;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class OrderPaymentRequestingService implements OrderPaymentRequesterInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderFinderInterface $orderFinder,
        private OrderPaymentFinderInterface $orderPaymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws OrderResultNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws OrderPaymentAlreadyRequestedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $orderId, int $itemCount, string $returnUrl): string
    {
        $result = $this->orderFinder->ofId($orderId);

        if ($result->status->isCancelled()) {
            throw OrderAlreadyCancelledException::forId(OrderId::fromString($orderId));
        }

        if (null !== $this->orderPaymentFinder->ofOrder($orderId)) {
            throw OrderPaymentAlreadyRequestedException::forOrderId($orderId);
        }

        $order = $this->orderRepository->load(OrderId::fromString($orderId));

        $session = $this->paymentGateway->requestPayment($orderId, $result->totalAmountInCents, $itemCount, $returnUrl);

        $this->commandBus->dispatch(new RequestOrderPayment(
            id: OrderPaymentId::forOrder($orderId)->toString(),
            orderId: $orderId,
            customerId: $result->customerId,
            buyerAddress: $order->buyerAddress(),
            amountInCents: $result->totalAmountInCents,
            reference: $session->reference,
            checkoutUrl: $session->checkoutUrl,
        ));

        return $session->checkoutUrl;
    }
}
