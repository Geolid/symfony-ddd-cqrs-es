<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('iam.authentication.drop_api_key_credential_cipher_key_on_identity_erased')]
final readonly class DropApiKeyCredentialCipherKeyOnIdentityErased
{
    public function __construct(private CipherKeyDropperInterface $cipherKeyDropper)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->cipherKeyDropper->drop($event->identityId);
    }
}
