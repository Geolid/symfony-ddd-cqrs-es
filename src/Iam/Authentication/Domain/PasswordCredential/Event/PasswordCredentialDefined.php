<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('iam.authentication.password_credential.defined')]
final readonly class PasswordCredentialDefined implements DomainEventInterface
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-%s'))]
        public string $login,
        public string $hash,
        public string $definedAt,
    ) {
    }
}
