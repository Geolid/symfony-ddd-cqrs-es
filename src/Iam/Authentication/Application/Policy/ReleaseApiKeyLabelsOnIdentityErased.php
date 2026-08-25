<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy\Policy;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[Policy('iam.authentication.release_api_key_labels_on_identity_erased')]
final readonly class ReleaseApiKeyLabelsOnIdentityErased
{
    public function __construct(private UniqueValueRegistryInterface $uniqueValues)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->uniqueValues->releaseAll(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $event->identityId));
    }
}
