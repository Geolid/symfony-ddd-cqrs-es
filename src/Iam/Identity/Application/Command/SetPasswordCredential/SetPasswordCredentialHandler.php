<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SetPasswordCredential;

use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
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
use Shared\Domain\Exception\AggregateNotFoundException;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class SetPasswordCredentialHandler
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
     * @throws AggregateNotFoundException
     * @throws IdentityNotActiveException
     * @throws LoginAlreadyTakenException
     */
    public function __invoke(SetPasswordCredential $command): void
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

            $credential = PasswordCredential::set(
                id: $id,
                identityId: $identityId,
                login: $login,
                plainPassword: $command->password,
                hasher: $this->hasher,
                setAt: $this->clock->now(),
            );
        }

        $this->repository->save($credential);
    }
}
