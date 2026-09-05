<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Query\CanRequestWithdrawal;

use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Return\Domain\Specification\WithdrawalWindowExpiredSpecification;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class CanRequestWithdrawalHandler
{
    public function __construct(
        private DeliveredOrderFinderInterface $orderFinder,
        private WithdrawalFinderInterface $withdrawalFinder,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CanRequestWithdrawal $query): bool
    {
        try {
            $order = $this->orderFinder->ofId($query->orderId);
        } catch (DeliveredOrderResultNotFoundException) {
            return false;
        }

        if ($this->withdrawalFinder->byOrder($query->orderId)->active()->count() > 0) {
            return false;
        }

        return !new WithdrawalWindowExpiredSpecification($this->clock->now())->isSatisfiedBy($order->deliveredAt);
    }
}
