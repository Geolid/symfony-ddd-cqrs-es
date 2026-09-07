<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\DerivedUuidTrait;

final readonly class SubjectId implements AggregateRootId
{
    use DerivedUuidTrait;
}
