<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\CipherKey\CipherKeyDropperInterface;
use Shared\Application\Policy;

#[Policy('iam.authentication.drop_api_key_credential_cipher_keys_on_identity_erased')]
final readonly class DropApiKeyCredentialCipherKeysOnIdentityErased
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private CipherKeyDropperInterface $cipherKeyDropper,
    ) {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        foreach ($this->apiKeyCredentialFinder->byIdentity($event->identityId) as $apiKeyCredential) {
            $this->cipherKeyDropper->drop($apiKeyCredential->id);
        }
    }
}
