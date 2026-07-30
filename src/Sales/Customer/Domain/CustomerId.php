<?php

declare(strict_types=1);

namespace Sales\Customer\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class CustomerId implements AggregateRootId
{
    use UuidTrait;
}
