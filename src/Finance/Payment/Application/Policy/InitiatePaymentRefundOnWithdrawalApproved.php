<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use Finance\Payment\Application\Command\InitiatePaymentRefund\InitiatePaymentRefund;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.payment.initiate_payment_refund_on_withdrawal_approved')]
final readonly class InitiatePaymentRefundOnWithdrawalApproved
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
        $this->commandBus->dispatch(new InitiatePaymentRefund(
            PaymentId::forOrder($event->orderId)->toString(),
        ));
    }
}
