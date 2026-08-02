<?php

declare(strict_types=1);

namespace Catalog\Product\Application\Query\GetProduct;

use Catalog\Product\Application\Finder\Product\ProductResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<ProductResult>
 */
final readonly class GetProduct implements QueryInterface
{
    public function __construct(public string $id)
    {
    }
}
