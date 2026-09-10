<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\Command\DefinePasswordCredential\Exception\PasswordCredentialLoginAlreadyInUseException;
use Iam\Authentication\Application\Password\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Password\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\PasswordCredentialUniqueKey;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class DefinePasswordCredentialHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private PasswordStrengthInterface $passwordStrength,
        private CompromisedPasswordGatewayInterface $compromisedPasswordGateway,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PasswordCredentialLoginAlreadyInUseException
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(DefinePasswordCredential $command): void
    {
        $id = PasswordCredentialId::forIdentity($command->identityId);
        $login = Login::fromString($command->login);
        $password = Password::fromString($command->password);

        if ($this->compromisedPasswordGateway->isCompromised($password)) {
            throw CompromisedPasswordException::forIdentity($command->identityId);
        }

        try {
            $this->uniqueValues->claim(UniqueKey::for(PasswordCredentialUniqueKey::LOGIN), $login->value, $id->toString());
        } catch (UniquenessViolatedException $e) {
            throw PasswordCredentialLoginAlreadyInUseException::forLogin($login->value, $e);
        }

        $credential = PasswordCredential::define(
            id: $id,
            identityId: $command->identityId,
            login: $login,
            password: $password,
            passwordStrength: $this->passwordStrength,
            hasher: $this->hasher,
            definedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
