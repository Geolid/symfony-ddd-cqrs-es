<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\CipherKey;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;

#[Processor('iam.authentication.drop_password_credential_cipher_key_on_identity_erased')]
final readonly class DropPasswordCredentialCipherKeyOnIdentityErased
{
    public function __construct(private CipherKeyStore $cipherKeyStore)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->cipherKeyStore->removeWithSubjectId(PasswordCredentialId::forIdentity($event->identityId)->toString());
    }
}
