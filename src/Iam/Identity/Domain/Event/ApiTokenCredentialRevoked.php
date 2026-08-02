<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.identity.api_token_credential_revoked')]
final readonly class ApiTokenCredentialRevoked implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $revokedAt,
    ) {
    }
}
