<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Finder\Shopper;

interface ShopperFinderInterface
{
    public function ofIdOrNull(string $shopperId): ?ShopperResult;
}
