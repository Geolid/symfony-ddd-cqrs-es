<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<OrderResult>
 */
interface OrderFinderInterface extends PaginatedCollectionFinderInterface
{
    public function ofId(string $id): ?OrderResult;

    public function withCustomer(string $customerId): static;
}
