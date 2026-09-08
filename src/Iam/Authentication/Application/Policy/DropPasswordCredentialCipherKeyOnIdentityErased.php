<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('iam.authentication.drop_password_credential_cipher_key_on_identity_erased')]
final readonly class DropPasswordCredentialCipherKeyOnIdentityErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->cipherKeyDropper->drop(PasswordCredentialId::forIdentity($event->identityId)->toString());
    }
}
