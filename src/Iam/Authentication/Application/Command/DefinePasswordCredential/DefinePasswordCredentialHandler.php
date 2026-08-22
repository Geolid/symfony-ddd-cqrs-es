<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\Exception\LoginAlreadyTakenException;
use Iam\Authentication\Domain\PasswordCredential\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[AsCommandHandler]
final readonly class DefinePasswordCredentialHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private PasswordPolicyInterface $policy,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws LoginAlreadyTakenException
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(DefinePasswordCredential $command): void
    {
        $id = PasswordCredentialId::forIdentity($command->identityId);
        $login = Login::fromString($command->login);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), $login->value, $id->toString(), $command->identityId);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw LoginAlreadyTakenException::forLogin($login->value, $e);
        }

        $credential = PasswordCredential::define(
            id: $id,
            identityId: $command->identityId,
            login: $login,
            password: Password::fromString($command->password),
            policy: $this->policy,
            hasher: $this->hasher,
            definedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
