<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<OrderResult>
 */
interface OrderFinderInterface extends PaginatedFinderInterface
{
    public function getById(string $id): ?OrderResult;

    public function withCustomer(string $customerId): static;
}
