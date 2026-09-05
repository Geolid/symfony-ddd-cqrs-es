<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class WithdrawalId implements AggregateRootId
{
    use UuidTrait;
}
