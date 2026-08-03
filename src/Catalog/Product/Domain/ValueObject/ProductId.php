<?php

declare(strict_types=1);

namespace Catalog\Product\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class ProductId implements AggregateRootId
{
    use UuidTrait;
}
