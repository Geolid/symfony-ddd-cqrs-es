<?php

declare(strict_types=1);

namespace Crm\Customer\Domain\Customer\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class CustomerId implements AggregateRootId
{
    use UuidTrait;

    private const string IDENTITY_NAMESPACE = '7c1a2f3e-4b5d-4e6f-8a9b-0c1d2e3f4a5b';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
