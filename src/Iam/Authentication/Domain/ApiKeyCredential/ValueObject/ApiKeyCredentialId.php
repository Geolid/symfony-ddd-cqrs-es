<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Shared\Domain\UuidTrait;

final readonly class ApiKeyCredentialId implements AggregateRootId
{
    use UuidTrait;
}
