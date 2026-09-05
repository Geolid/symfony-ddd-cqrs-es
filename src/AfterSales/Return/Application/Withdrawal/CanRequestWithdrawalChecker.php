<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Withdrawal;

use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderResult;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalResult;
use AfterSales\Return\Domain\Specification\WithdrawalWindowExpiredSpecification;
use Psr\Clock\ClockInterface;

final readonly class CanRequestWithdrawalChecker implements CanRequestWithdrawalInterface
{
    public function __construct(
        private DeliveredOrderFinderInterface $orderFinder,
        private WithdrawalFinderInterface $withdrawalFinder,
        private ClockInterface $clock,
    ) {
    }

    public function forOrder(string $orderId): bool
    {
        try {
            $order = $this->orderFinder->ofId($orderId);
        } catch (DeliveredOrderResultNotFoundException) {
            return false;
        }

        if ($this->withdrawalFinder->byOrder($orderId)->active()->count() > 0) {
            return false;
        }

        return !new WithdrawalWindowExpiredSpecification($this->clock->now())->isSatisfiedBy($order->deliveredAt);
    }

    /**
     * @return array<string, bool>
     */
    public function forOrders(string ...$orderIds): array
    {
        if ([] === $orderIds) {
            return [];
        }

        /** @var array<string, DeliveredOrderResult> $orders */
        $orders = iterator_to_array($this->orderFinder->byIds(...$orderIds)->indexBy(
            static fn (DeliveredOrderResult $result): string => $result->orderId,
        ));

        $activeOrderIds = iterator_to_array($this->withdrawalFinder->byOrders(...$orderIds)->active()->indexBy(
            static fn (WithdrawalResult $result): string => $result->orderId,
        ));

        $now = $this->clock->now();

        return array_combine(
            $orderIds,
            array_map(
                static function (string $orderId) use ($orders, $activeOrderIds, $now): bool {
                    $order = $orders[$orderId] ?? null;

                    if (null === $order || isset($activeOrderIds[$orderId])) {
                        return false;
                    }

                    return !new WithdrawalWindowExpiredSpecification($now)->isSatisfiedBy($order->deliveredAt);
                },
                $orderIds,
            ),
        );
    }
}
