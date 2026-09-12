<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\CheckoutSessionItem;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<CheckoutSessionItemResult>
 */
interface CheckoutSessionItemFinderInterface extends IterableFinderInterface
{
    public function byCheckoutSession(string $checkoutSessionId): static;
}
