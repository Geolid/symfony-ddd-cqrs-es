<?php

declare(strict_types=1);

namespace Finance\Refund\Application\Policy;

use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use Finance\Refund\Application\Command\InitiateRefund\InitiateRefund;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Policy;

#[Policy('finance.refund.initiate_refund_on_withdrawal_approved')]
final readonly class InitiateRefundOnWithdrawalApproved
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
        $this->commandBus->dispatch(new InitiateRefund($event->orderId));
    }
}
