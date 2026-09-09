<?php

declare(strict_types=1);

namespace Sales\Ordering\Domain\Cart\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class CartId implements AggregateRootId
{
    use UuidTrait;
}
