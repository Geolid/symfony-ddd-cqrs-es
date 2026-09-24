<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\DeviceTrust\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class DeviceTrustId implements AggregateRootId
{
    use UuidTrait;

    private const string IDENTITY_NAMESPACE = '2a6e9f3c-8b1d-4c2e-9f7a-5d3b6c8e1a2f';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
