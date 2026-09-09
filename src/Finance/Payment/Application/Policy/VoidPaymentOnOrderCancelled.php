<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\VoidPayment\VoidPayment;
use Finance\Payment\Application\Finder\Payment\Exception\PaymentResultNotFoundException;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.void_payment_on_order_cancelled')]
final readonly class VoidPaymentOnOrderCancelled
{
    public function __construct(
        private PaymentFinderInterface $paymentFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderCancelledIntegrationEvent::class)]
    public function __invoke(OrderCancelledIntegrationEvent $event): void
    {
        try {
            $payment = $this->paymentFinder->ofOrderId($event->orderId);
        } catch (PaymentResultNotFoundException) {
            return;
        }

        $this->commandBus->dispatch(new VoidPayment($payment->id));
    }
}
