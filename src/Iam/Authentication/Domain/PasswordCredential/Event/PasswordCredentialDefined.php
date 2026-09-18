<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.password_credential.defined')]
final readonly class PasswordCredentialDefined
{
    public function __construct(
        public PasswordCredentialId $id,
        public string $identityId,
        public string $passwordHash,
        public \DateTimeImmutable $definedAt,
    ) {
    }
}
