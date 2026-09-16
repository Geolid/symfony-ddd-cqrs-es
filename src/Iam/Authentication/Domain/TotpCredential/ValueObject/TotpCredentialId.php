<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\TotpCredential\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class TotpCredentialId implements AggregateRootId
{
    use UuidTrait;
}
