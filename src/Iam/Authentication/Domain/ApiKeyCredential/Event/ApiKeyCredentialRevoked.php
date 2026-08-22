<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.authentication.api_key_credential.revoked')]
final readonly class ApiKeyCredentialRevoked implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $revokedAt,
    ) {
    }
}
