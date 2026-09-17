<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DefinePasswordCredential;

use Iam\Authentication\Application\BreachDatabase\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\BreachDatabase\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class DefinePasswordCredentialHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private PasswordStrengthSpecificationInterface $passwordStrengthSpecification,
        private CompromisedPasswordGatewayInterface $compromisedPasswordGateway,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(DefinePasswordCredential $command): void
    {
        $id = PasswordCredentialId::forIdentity($command->identityId);
        $password = Password::fromString($command->password);

        if ($this->compromisedPasswordGateway->isCompromised($password)) {
            throw CompromisedPasswordException::forIdentity($command->identityId);
        }

        $credential = PasswordCredential::define(
            id: $id,
            identityId: $command->identityId,
            password: $password,
            passwordStrengthSpecification: $this->passwordStrengthSpecification,
            hasher: $this->hasher,
            definedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
