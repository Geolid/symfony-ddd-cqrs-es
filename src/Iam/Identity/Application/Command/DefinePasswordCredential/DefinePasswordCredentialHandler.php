<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\DefinePasswordCredential;

use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Exception\CompromisedPasswordException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Exception\PasswordUnchangedException;
use Iam\Identity\Domain\Exception\WeakPasswordException;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\PasswordPolicyInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\Password;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Iam\Identity\Domain\ValueObject\PasswordCredentialUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[AsCommandHandler]
final readonly class DefinePasswordCredentialHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identities,
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private PasswordPolicyInterface $policy,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws PasswordCredentialNotFoundException
     * @throws IdentityNotActiveException
     * @throws LoginAlreadyTakenException
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws PasswordUnchangedException
     */
    public function __invoke(DefinePasswordCredential $command): void
    {
        $identityId = IdentityId::fromString($command->identityId);
        $this->identities->load($identityId)->ensureActive();

        $id = PasswordCredentialId::forIdentity($identityId->toString());
        $password = Password::fromString($command->password);

        if ($this->repository->has($id)) {
            $credential = $this->repository->load($id);
            $credential->change($password, $this->policy, $this->hasher, $this->clock->now());
        } else {
            $login = Login::fromString($command->login);

            try {
                $this->uniqueValues->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), $login->value, $id->toString(), $identityId->toString());
            } catch (UniqueValueAlreadyTakenException $e) {
                throw LoginAlreadyTakenException::forLogin($login->value, $e);
            }

            $credential = PasswordCredential::define(
                id: $id,
                identityId: $identityId,
                login: $login,
                password: $password,
                policy: $this->policy,
                hasher: $this->hasher,
                definedAt: $this->clock->now(),
            );
        }

        try {
            $this->repository->save($credential);
        } catch (PasswordCredentialAlreadyExistsException) {
            return;
        }
    }
}
