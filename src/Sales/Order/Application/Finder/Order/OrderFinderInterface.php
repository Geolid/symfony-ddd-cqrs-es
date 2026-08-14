<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<OrderResult>
 */
interface OrderFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofId(string $id): OrderResult;

    public function byCustomer(string $customerId): static;

    public function placedBefore(string $cutoff): static;
}
