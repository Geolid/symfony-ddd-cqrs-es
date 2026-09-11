<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\CheckoutSession;

use Sales\Ordering\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;

interface CheckoutSessionFinderInterface
{
    /**
     * @throws CheckoutSessionResultNotFoundException
     */
    public function ofId(string $checkoutSessionId): CheckoutSessionResult;
}
