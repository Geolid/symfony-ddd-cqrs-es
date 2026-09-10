<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Order;

use Sales\Ordering\Application\Finder\Order\Exception\OrderResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<OrderResult>
 */
interface OrderFinderInterface extends IterableFinderInterface
{
    /**
     * @throws OrderResultNotFoundException
     */
    public function ofId(string $id): OrderResult;

    public function byShopper(string $shopperId): static;
}
