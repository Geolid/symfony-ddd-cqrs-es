<?php

declare(strict_types=1);

namespace Sales\Order\Application\Product;

interface ProductResolverInterface
{
    public function resolveFor(string $productId): ?Product;
}
