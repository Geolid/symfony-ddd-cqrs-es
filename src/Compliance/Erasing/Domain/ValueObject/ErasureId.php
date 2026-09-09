<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class ErasureId implements AggregateRootId
{
    use UuidTrait;
}
