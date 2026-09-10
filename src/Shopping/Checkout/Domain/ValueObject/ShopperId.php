<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class ShopperId implements AggregateRootId
{
    use UuidTrait;

    private const string IDENTITY_NAMESPACE = 'a327c349-abeb-4b09-b2bd-a707bae25986';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
