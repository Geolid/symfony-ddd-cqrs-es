<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\CipherKey;

use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use Shared\Infrastructure\Processor;

#[Processor('iam.authentication.drop_api_key_credential_cipher_keys_on_identity_erased')]
final readonly class DropApiKeyCredentialCipherKeysOnIdentityErased
{
    public function __construct(
        private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder,
        private CipherKeyStore $cipherKeyStore,
    ) {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        foreach ($this->apiKeyCredentialFinder->byIdentity($event->identityId) as $apiKeyCredential) {
            $this->cipherKeyStore->removeWithSubjectId($apiKeyCredential->id);
        }
    }
}
