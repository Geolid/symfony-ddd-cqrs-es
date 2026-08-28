<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('iam.authentication.api_key_credential.issued')]
final readonly class ApiKeyCredentialIssued
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-%s'))]
        public string $label,
        public string $keyId,
        public string $secretHash,
        public \DateTimeImmutable $issuedAt,
    ) {
    }
}
