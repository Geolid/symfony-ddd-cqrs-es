<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSession;

use Shared\Application\Finder\IterableFinderInterface;
use Shopping\Checkout\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;

/**
 * @extends IterableFinderInterface<CheckoutSessionResult>
 */
interface CheckoutSessionFinderInterface extends IterableFinderInterface
{
    /**
     * @throws CheckoutSessionResultNotFoundException
     */
    public function ofId(string $id): CheckoutSessionResult;

    public function ofCartOrNull(string $cartId): ?CheckoutSessionResult;

    public function byCustomer(string $customerId): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
