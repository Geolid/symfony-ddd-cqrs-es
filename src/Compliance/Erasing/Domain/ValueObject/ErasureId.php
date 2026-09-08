<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class ErasureId implements AggregateRootId
{
    use UuidTrait;

    private const string IDENTITY_NAMESPACE = '5e1c2e83-d133-40db-b766-9f180f9f154c';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
