<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Customer;

interface CustomerFinderInterface
{
    public function ofIdOrNull(string $id): ?CustomerResult;
}
