<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Shared\Domain\Event\DomainEventInterface;

#[Event('iam.authentication.api_key_credential.issued')]
final readonly class ApiKeyCredentialIssued implements DomainEventInterface
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        public string $label,
        public string $keyId,
        public string $secretHash,
        public string $issuedAt,
    ) {
    }
}
