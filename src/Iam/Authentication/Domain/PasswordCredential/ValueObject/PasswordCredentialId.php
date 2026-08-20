<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\ValueObject;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Ramsey\Uuid\Uuid;
use Shared\Domain\DerivedUuidTrait;

final readonly class PasswordCredentialId implements AggregateRootId
{
    use DerivedUuidTrait;

    private const string IDENTITY_NAMESPACE = 'e435bd5a-3120-4d25-8d26-89e9523b110e';

    public static function forIdentity(string $identityId): self
    {
        return new self(Uuid::uuid5(self::IDENTITY_NAMESPACE, $identityId)->toString());
    }
}
