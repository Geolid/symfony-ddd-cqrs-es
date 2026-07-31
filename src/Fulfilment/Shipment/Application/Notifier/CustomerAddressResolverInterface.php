<?php

declare(strict_types=1);

namespace Fulfilment\Shipment\Application\Notifier;

interface CustomerAddressResolverInterface
{
    public function resolveFor(string $customerId): ?string;
}
