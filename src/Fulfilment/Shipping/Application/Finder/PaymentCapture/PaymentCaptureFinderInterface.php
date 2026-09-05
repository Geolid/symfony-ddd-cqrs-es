<?php

declare(strict_types=1);

namespace Fulfilment\Shipping\Application\Finder\PaymentCapture;

interface PaymentCaptureFinderInterface
{
    public function ofOrderOrNull(string $orderId): ?PaymentCaptureResult;
}
