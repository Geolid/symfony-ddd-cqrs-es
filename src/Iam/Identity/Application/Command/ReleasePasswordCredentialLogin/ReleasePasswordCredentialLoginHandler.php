<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReleasePasswordCredentialLogin;

use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class ReleasePasswordCredentialLoginHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws PasswordCredentialNotFoundException
     */
    public function __invoke(ReleasePasswordCredentialLogin $command): void
    {
        $id = PasswordCredentialId::forIdentity($command->identityId);

        if (!$this->repository->has($id)) {
            return;
        }

        $credential = $this->repository->load($id);

        $this->uniqueValues->release(PasswordCredentialUniqueValue::LOGIN, $credential->login()->fingerprint());
    }
}
