<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\CheckoutSession\Repository;

use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;

interface CheckoutSessionRepositoryInterface
{
    public function has(CheckoutSessionId $id): bool;

    /**
     * @throws CheckoutSessionNotFoundException
     */
    public function load(CheckoutSessionId $id): CheckoutSession;

    /**
     * @throws CheckoutSessionAlreadyExistsException
     */
    public function save(CheckoutSession $checkoutSession): void;
}
