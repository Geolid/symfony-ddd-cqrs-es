<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\PasswordCredential;

final readonly class PasswordCredentialResult
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $login,
        public string $passwordHash,
        public \DateTimeImmutable $definedAt,
        public \DateTimeImmutable $passwordChangedAt,
        public bool $identityAuthenticatable,
    ) {
    }
}
