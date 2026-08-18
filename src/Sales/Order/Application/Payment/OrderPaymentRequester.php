<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderPaymentRepositoryInterface;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class OrderPaymentRequester implements OrderPaymentRequesterInterface
{
    public function __construct(
        private OrderPaymentRepositoryInterface $orderPaymentRepository,
        private OrderRepositoryInterface $orderRepository,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     * @throws OrderAlreadyCancelledException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $orderId, string $returnUrl): string
    {
        $order = $this->orderRepository->load(OrderId::fromString($orderId));
        $order->ensureNotCancelled();

        $orderPaymentId = OrderPaymentId::forOrder($orderId);

        if ($this->orderPaymentRepository->has($orderPaymentId)) {
            return $this->orderPaymentRepository->load($orderPaymentId)->checkoutUrl;
        }

        $session = $this->paymentGateway->requestPayment($orderId, $order->totalAmountInCents, $returnUrl, $order->billingAddress);

        $this->commandBus->dispatch(new RequestOrderPayment(
            id: $orderPaymentId->toString(),
            orderId: $orderId,
            amountInCents: $order->totalAmountInCents,
            reference: $session->reference,
            checkoutUrl: $session->checkoutUrl,
        ));

        return $session->checkoutUrl;
    }
}
