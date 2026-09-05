<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Event;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\Gdpr\ErasedValueObjectSentinel;
use Shared\Domain\ValueObject\Label;

#[Event('iam.authentication.api_key_credential.issued')]
final readonly class ApiKeyCredentialIssued
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedValueObjectSentinel(new ErasedFieldSentinel('erased-%s'), Label::class, 'fromString'))]
        public Label $label,
        public KeyId $keyId,
        public string $secretHash,
        public \DateTimeImmutable $issuedAt,
    ) {
    }
}
