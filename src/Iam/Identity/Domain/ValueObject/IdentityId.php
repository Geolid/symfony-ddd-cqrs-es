<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class IdentityId implements AggregateRootId
{
    use UuidTrait;
}
