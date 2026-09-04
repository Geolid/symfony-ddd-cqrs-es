<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalApproved;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.after_sales.withdrawal.withdrawal.approved')]
final readonly class WithdrawalApprovedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $withdrawalId,
        public string $orderId,
        public \DateTimeImmutable $approvedAt,
    ) {
    }
}
