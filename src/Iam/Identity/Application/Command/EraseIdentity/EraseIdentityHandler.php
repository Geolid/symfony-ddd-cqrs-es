<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class EraseIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private PasswordCredentialRepositoryInterface $passwordCredentialRepository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws PasswordCredentialNotFoundException
     */
    public function __invoke(EraseIdentity $command): void
    {
        $identityId = IdentityId::fromString($command->id);
        $identity = $this->identityRepository->load($identityId);

        if ($identity->isErased()) {
            return;
        }

        $identity->erase($this->clock->now());
        $this->identityRepository->save($identity);

        $credentialId = PasswordCredentialId::forIdentity($identityId->toString());

        if ($this->passwordCredentialRepository->has($credentialId)) {
            $credential = $this->passwordCredentialRepository->load($credentialId);
            $this->uniqueValues->release(PasswordCredentialUniqueValue::LOGIN, $credential->login()->fingerprint());
        }
    }
}
