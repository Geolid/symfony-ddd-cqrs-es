<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\DefinePasswordCredential;

use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueValue;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class DefinePasswordCredentialHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identities,
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws PasswordCredentialNotFoundException
     * @throws IdentityNotActiveException
     * @throws LoginAlreadyTakenException
     */
    public function __invoke(DefinePasswordCredential $command): void
    {
        $identityId = IdentityId::fromString($command->identityId);
        $this->identities->load($identityId)->ensureActive();

        $id = PasswordCredentialId::forIdentity($identityId->toString());

        if ($this->repository->has($id)) {
            $credential = $this->repository->load($id);
            $credential->change($command->password, $this->hasher, $this->clock->now());
        } else {
            $login = Login::fromString($command->login);
            $fingerprint = $login->fingerprint();

            try {
                $this->uniqueValues->reserve(PasswordCredentialUniqueValue::LOGIN, $fingerprint);
            } catch (UniqueValueAlreadyTakenException $e) {
                throw LoginAlreadyTakenException::forFingerprint($fingerprint, $e);
            }

            $credential = PasswordCredential::define(
                id: $id,
                identityId: $identityId,
                login: $login,
                plainPassword: $command->password,
                hasher: $this->hasher,
                definedAt: $this->clock->now(),
            );
        }

        $this->repository->save($credential);
    }
}
