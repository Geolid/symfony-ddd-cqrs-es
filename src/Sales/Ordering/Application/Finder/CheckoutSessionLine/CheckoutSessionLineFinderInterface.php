<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSessionLine;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<CheckoutSessionLineResult>
 */
interface CheckoutSessionLineFinderInterface extends IterableFinderInterface
{
    public function byCheckoutSession(string $checkoutSessionId): static;
}
