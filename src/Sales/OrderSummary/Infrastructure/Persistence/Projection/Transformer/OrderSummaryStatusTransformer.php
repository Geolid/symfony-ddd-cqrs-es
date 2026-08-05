<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Transformer;

use Sales\OrderSummary\Application\Enum\AppOrderSummaryStatus;

final readonly class OrderSummaryStatusTransformer
{
    public function compute(
        string $orderStatus,
        ?string $paymentStatus,
        ?string $shipmentStatus,
    ): AppOrderSummaryStatus {
        if ('cancelled' === $orderStatus) {
            return AppOrderSummaryStatus::CANCELLED;
        }

        return match ($paymentStatus) {
            null => AppOrderSummaryStatus::PLACED,
            'requested' => AppOrderSummaryStatus::PAYMENT_PENDING,
            default => match ($shipmentStatus) {
                null, 'pending' => AppOrderSummaryStatus::PREPARING,
                'dispatched' => AppOrderSummaryStatus::DISPATCHED,
                default => AppOrderSummaryStatus::DELIVERED,
            },
        };
    }
}
