<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class PasswordCredentialId implements AggregateRootId
{
    use UuidTrait;
}
