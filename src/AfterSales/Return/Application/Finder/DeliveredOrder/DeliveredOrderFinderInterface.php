<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Finder\DeliveredOrder;

use AfterSales\Return\Application\Exception\DeliveredOrderResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<DeliveredOrderResult>
 */
interface DeliveredOrderFinderInterface extends IterableFinderInterface
{
    /**
     * @throws DeliveredOrderResultNotFoundException
     */
    public function ofId(string $orderId): DeliveredOrderResult;

    public function byIds(string ...$orderIds): static;
}
