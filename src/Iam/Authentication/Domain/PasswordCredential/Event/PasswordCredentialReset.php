<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.password_credential.reset')]
final readonly class PasswordCredentialReset
{
    public function __construct(
        public PasswordCredentialId $id,
        public string $passwordHash,
        public \DateTimeImmutable $resetAt,
    ) {
    }
}
