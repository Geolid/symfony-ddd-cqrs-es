<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.password_credential.rehashed')]
final readonly class PasswordCredentialRehashed
{
    public function __construct(
        public string $id,
        public string $passwordHash,
        public string $rehashedAt,
    ) {
    }
}
