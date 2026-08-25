<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Processor;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Iam\Identity\Application\IntegrationEvent\IdentityErased\IdentityErasedIntegrationEvent;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\Processor;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[Processor('iam.authentication.release_login_on_identity_erased')]
final readonly class ReleaseLoginOnIdentityErased
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws PasswordCredentialNotFoundException
     */
    #[Subscribe(IdentityErasedIntegrationEvent::class)]
    public function __invoke(IdentityErasedIntegrationEvent $event): void
    {
        $id = PasswordCredentialId::forIdentity($event->identityId);

        if (!$this->repository->has($id)) {
            return;
        }

        $credential = $this->repository->load($id);

        $this->uniqueValues->release(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), $credential->login->value, $credential->id->toString());
    }
}
