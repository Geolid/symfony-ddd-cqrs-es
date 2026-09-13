<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class CartId implements AggregateRootId
{
    use UuidTrait;
}
