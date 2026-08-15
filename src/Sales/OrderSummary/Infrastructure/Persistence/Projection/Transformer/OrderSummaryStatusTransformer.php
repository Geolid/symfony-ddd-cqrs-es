<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Transformer;

use Sales\OrderSummary\Application\Status\OrderSummaryStatus;

final readonly class OrderSummaryStatusTransformer
{
    public function compute(
        string $orderStatus,
        ?string $paymentStatus,
        ?string $shipmentStatus,
    ): OrderSummaryStatus {
        if ('cancelled' === $orderStatus) {
            return OrderSummaryStatus::CANCELLED;
        }

        return match ($paymentStatus) {
            null => OrderSummaryStatus::PLACED,
            'requested' => OrderSummaryStatus::PAYMENT_PENDING,
            default => match ($shipmentStatus) {
                null, 'requested', 'prepared', 'manifested' => OrderSummaryStatus::PREPARING,
                'dispatched' => OrderSummaryStatus::DISPATCHED,
                default => OrderSummaryStatus::DELIVERED,
            },
        };
    }
}
