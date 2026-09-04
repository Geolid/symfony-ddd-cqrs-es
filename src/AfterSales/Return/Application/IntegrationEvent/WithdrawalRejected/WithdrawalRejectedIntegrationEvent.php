<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Application\IntegrationEvent\IntegrationEventInterface;

#[Event('integration.after_sales.return.withdrawal.rejected')]
final readonly class WithdrawalRejectedIntegrationEvent implements IntegrationEventInterface
{
    public function __construct(
        public string $withdrawalId,
        public string $orderId,
        public string $reason,
        public \DateTimeImmutable $rejectedAt,
    ) {
    }
}
