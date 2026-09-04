<?php

declare(strict_types=1);

namespace Finance\Payer\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class PayerId implements AggregateRootId
{
    use UuidTrait;
}
