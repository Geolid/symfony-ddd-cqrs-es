<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Event;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('iam.authentication.api_key_credential.revoked')]
final readonly class ApiKeyCredentialRevoked
{
    public function __construct(
        public ApiKeyCredentialId $id,
        public \DateTimeImmutable $revokedAt,
    ) {
    }
}
