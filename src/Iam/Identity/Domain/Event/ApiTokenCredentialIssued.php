<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.identity.api_token_credential_issued')]
final readonly class ApiTokenCredentialIssued implements DomainEventInterface
{
    public function __construct(
        public string $id,
        public string $identityId,
        public string $identifier,
        public string $secretHash,
        public string $issuedAt,
        public string $expiresAt,
    ) {
    }
}
