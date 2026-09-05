<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Query\ListWithdrawalEligibleOrders;

use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderResult;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalResult;
use AfterSales\Return\Domain\Specification\WithdrawalWindowExpiredSpecification;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class ListWithdrawalEligibleOrdersHandler
{
    public function __construct(
        private DeliveredOrderFinderInterface $orderFinder,
        private WithdrawalFinderInterface $withdrawalFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return array<string, bool>
     */
    public function __invoke(ListWithdrawalEligibleOrders $query): array
    {
        /** @var array<string, DeliveredOrderResult> $orders */
        $orders = iterator_to_array($this->orderFinder->byIds(...$query->orderIds)->indexBy(
            static fn (DeliveredOrderResult $result): string => $result->orderId,
        ));

        $activeOrderIds = iterator_to_array($this->withdrawalFinder->byOrders(...$query->orderIds)->active()->indexBy(
            static fn (WithdrawalResult $result): string => $result->orderId,
        ));

        $now = $this->clock->now();

        return array_combine(
            $query->orderIds,
            array_map(
                static function (string $orderId) use ($orders, $activeOrderIds, $now): bool {
                    $order = $orders[$orderId] ?? null;

                    if (null === $order || isset($activeOrderIds[$orderId])) {
                        return false;
                    }

                    return !new WithdrawalWindowExpiredSpecification($now)->isSatisfiedBy($order->deliveredAt);
                },
                $query->orderIds,
            ),
        );
    }
}
