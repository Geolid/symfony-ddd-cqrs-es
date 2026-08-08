<?php

declare(strict_types=1);

namespace Iam\Access\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\DerivedUuidTrait;

final readonly class GrantId implements AggregateRootId
{
    use DerivedUuidTrait;

    private const string PERMISSION_NAMESPACE = 'd14cbd46-7a5b-44dd-af31-f907852ba3a7';

    public static function forIdentityAndPermission(string $identityId, string $permission): self
    {
        return new self(Uuid::uuid5(self::PERMISSION_NAMESPACE, $identityId.':'.$permission)->toString());
    }
}
