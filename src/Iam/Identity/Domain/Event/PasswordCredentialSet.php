<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Event;

use Patchlevel\EventSourcing\Attribute\Event;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\DataSubjectId;
use Patchlevel\Hydrator\Extension\Cryptography\Attribute\SensitiveData;
use Shared\Domain\Event\DomainEventInterface;
use Shared\Domain\Gdpr\ErasedFieldSentinel;

#[Event('iam.identity.password_credential_set')]
final readonly class PasswordCredentialSet implements DomainEventInterface
{
    public function __construct(
        public string $id,
        #[DataSubjectId]
        public string $identityId,
        #[SensitiveData(fallbackCallable: new ErasedFieldSentinel('erased-login-%s'))]
        public string $login,
        public string $hash,
        public string $setAt,
    ) {
    }
}
