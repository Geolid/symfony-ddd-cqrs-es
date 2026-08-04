<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Service;

final class OrderTrackingStatusResolver
{
    public function resolve(string $orderStatus, ?string $paymentStatus, ?string $shipmentStatus): string
    {
        if ('cancelled' === $orderStatus) {
            return 'cancelled';
        }

        if (null === $paymentStatus) {
            return 'placed';
        }

        if ('requested' === $paymentStatus) {
            return 'payment_pending';
        }

        if (null === $shipmentStatus || 'pending' === $shipmentStatus) {
            return 'preparing';
        }

        if ('dispatched' === $shipmentStatus) {
            return 'dispatched';
        }

        return 'delivered';
    }
}
