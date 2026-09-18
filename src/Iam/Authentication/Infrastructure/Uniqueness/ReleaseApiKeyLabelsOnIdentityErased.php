<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\Uniqueness;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Infrastructure\Processor;

#[Processor('iam.authentication.release_api_key_labels_on_identity_erased')]
final readonly class ReleaseApiKeyLabelsOnIdentityErased
{
    public function __construct(private UniquenessRegistryInterface $uniqueness)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->uniqueness->releaseAll(UniqueKey::for(AuthenticationUniqueKey::API_KEY_CREDENTIAL_LABEL, $event->identityId));
    }
}
