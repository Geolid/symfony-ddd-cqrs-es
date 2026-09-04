<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use Finance\Payment\Application\Command\CapturePayment\CapturePayment;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\IntegrationEvent\OrderDispatched\OrderDispatchedIntegrationEvent;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.capture_payment_on_order_dispatched')]
final readonly class CapturePaymentOnOrderDispatched
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(OrderDispatchedIntegrationEvent::class)]
    public function __invoke(OrderDispatchedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new CapturePayment(
            PaymentId::forOrder($event->orderId)->toString(),
        ));
    }
}
