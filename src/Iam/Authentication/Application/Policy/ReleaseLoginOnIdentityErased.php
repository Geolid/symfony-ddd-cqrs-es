<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Policy;

use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Policy;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[Policy('iam.authentication.release_login_on_identity_erased')]
final readonly class ReleaseLoginOnIdentityErased
{
    public function __construct(private UniqueValueRegistryInterface $uniqueValues)
    {
    }

    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $this->uniqueValues->release(
            UniqueKey::for(PasswordCredentialUniqueKey::LOGIN),
            PasswordCredentialId::forIdentity($event->identityId)->toString(),
        );
    }
}
