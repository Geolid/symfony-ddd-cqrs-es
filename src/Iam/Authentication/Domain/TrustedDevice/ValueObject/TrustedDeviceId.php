<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TrustedDevice\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class TrustedDeviceId implements AggregateRootId
{
    use UuidTrait;
}
