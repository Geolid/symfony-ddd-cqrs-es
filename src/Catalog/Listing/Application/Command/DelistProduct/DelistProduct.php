<?php

declare(strict_types=1);

namespace Catalog\Listing\Application\Command\DelistProduct;

use Shared\Application\Command\CommandInterface;

final readonly class DelistProduct implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
