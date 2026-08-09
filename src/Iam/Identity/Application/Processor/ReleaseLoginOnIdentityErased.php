<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Processor;

use Iam\Identity\Domain\Event\IdentityErased;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\SyncProcessorGroup;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[Processor('iam.identity.release_login_on_identity_erased', group: SyncProcessorGroup::NAME)]
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
    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $credentialId = PasswordCredentialId::forIdentity($event->id);

        if (!$this->repository->has($credentialId)) {
            return;
        }

        $credential = $this->repository->load($credentialId);
        $this->uniqueValues->release(PasswordCredentialUniqueValue::LOGIN, $credential->login()->fingerprint());
    }
}
