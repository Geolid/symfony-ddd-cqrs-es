<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Finder\Shopper;

use Shopping\Checkout\Application\Finder\Shopper\Exception\ShopperResultNotFoundException;

interface ShopperFinderInterface
{
    /**
     * @throws ShopperResultNotFoundException
     */
    public function ofId(string $id): ShopperResult;
}
