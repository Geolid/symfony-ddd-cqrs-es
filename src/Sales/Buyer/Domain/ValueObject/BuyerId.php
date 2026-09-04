<?php

declare(strict_types=1);

namespace Sales\Buyer\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class BuyerId implements AggregateRootId
{
    use UuidTrait;
}
