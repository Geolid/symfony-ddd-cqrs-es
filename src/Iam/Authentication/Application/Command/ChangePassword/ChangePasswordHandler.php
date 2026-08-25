<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Credential\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandUseCase;

#[CommandUseCase]
final readonly class ChangePasswordHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private PasswordStrengthInterface $passwordStrength,
        private CompromisedPasswordGatewayInterface $compromisedPasswordGateway,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PasswordCredentialNotFoundException
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws SamePasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(ChangePassword $command): void
    {
        $password = Password::fromString($command->password);

        if ($this->compromisedPasswordGateway->isCompromised($password)) {
            throw CompromisedPasswordException::forIdentity($command->identityId);
        }

        $credential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $credential->change($password, $this->passwordStrength, $this->hasher, $this->clock->now());

        $this->repository->save($credential);
    }
}
