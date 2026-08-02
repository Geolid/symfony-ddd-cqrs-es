<?php

declare(strict_types=1);

namespace Iam\Access\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class GrantId implements AggregateRootId
{
    use UuidTrait;
}
