<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Shopper;

interface ShopperFinderInterface
{
    public function ofIdOrNull(string $id): ?ShopperResult;
}
