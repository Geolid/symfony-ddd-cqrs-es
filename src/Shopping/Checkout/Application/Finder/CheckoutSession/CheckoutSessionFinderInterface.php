<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSession;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<CheckoutSessionResult>
 */
interface CheckoutSessionFinderInterface extends IterableFinderInterface
{
    public function ofCartOrNull(string $cartId): ?CheckoutSessionResult;

    public function byShopper(string $shopperId): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
