<?php

declare(strict_types=1);

namespace Sales\Order\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Command\InitiateOrderPaymentRefund\InitiateOrderPaymentRefund;
use Sales\Order\Domain\ValueObject\OrderPaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('sales.order.initiate_order_payment_refund_on_withdrawal_approved')]
final readonly class InitiateOrderPaymentRefundOnWithdrawalApproved
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    #[Subscribe(WithdrawalApprovedIntegrationEvent::class)]
    public function __invoke(WithdrawalApprovedIntegrationEvent $event): void
    {
        $this->commandBus->dispatch(new InitiateOrderPaymentRefund(
            OrderPaymentId::forOrder($event->orderId)->toString(),
        ));
    }
}
