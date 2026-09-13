<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Repository;

use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;

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
