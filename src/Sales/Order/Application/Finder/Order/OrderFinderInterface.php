<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\Order;

use Sales\Order\Application\Exception\OrderResultNotFoundException;
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

    public function byCustomer(string $customerId): static;

    public function closedBefore(\DateTimeImmutable $cutoff): static;

    public function deliveredBefore(\DateTimeImmutable $cutoff): static;
}
