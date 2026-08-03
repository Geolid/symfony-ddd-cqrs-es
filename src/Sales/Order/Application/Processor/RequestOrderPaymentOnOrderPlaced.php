<?php

declare(strict_types=1);

namespace Sales\Order\Application\Processor;

use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\RequestOrderPayment\RequestOrderPayment;
use Sales\Order\Application\Gateway\PaymentGatewayInterface;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

#[Processor('sales.order.request_order_payment_on_order_placed')]
final readonly class RequestOrderPaymentOnOrderPlaced
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderPlaced::class)]
    public function __invoke(OrderPlaced $event): void
    {
        $this->commandBus->dispatch(new RequestOrderPayment(
            id: OrderPaymentId::forOrder($event->id)->toString(),
            orderId: $event->id,
            customerId: $event->customerId,
            buyerAddress: $event->buyerAddress,
            amountInCents: $event->totalAmountInCents,
            reference: $this->paymentGateway->requestPayment($event->id, $event->totalAmountInCents),
        ));
    }
}
