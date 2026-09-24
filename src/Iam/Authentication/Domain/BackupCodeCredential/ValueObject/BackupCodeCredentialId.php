<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\UuidTrait;

final readonly class BackupCodeCredentialId implements AggregateRootId
{
    use UuidTrait;

    private const string IDENTITY_NAMESPACE = 'f3a1c9d2-6b4e-4a7f-8c3d-1e9b7a2f5c6d';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
