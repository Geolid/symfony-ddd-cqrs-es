<?php

declare(strict_types=1);

namespace Ordering\Order\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class OrderId implements AggregateRootId
{
    use UuidTrait;
}
