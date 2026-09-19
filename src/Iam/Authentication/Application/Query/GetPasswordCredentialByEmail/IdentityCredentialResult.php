<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetPasswordCredentialByEmail;

final readonly class IdentityCredentialResult
{
    public function __construct(
        public string $identityId,
        public string $email,
        public bool $identityAuthenticatable,
        public \DateTimeImmutable $passwordChangedAt,
    ) {
    }
}
