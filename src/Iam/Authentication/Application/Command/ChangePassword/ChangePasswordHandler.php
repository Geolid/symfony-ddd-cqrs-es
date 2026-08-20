<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ChangePassword;

use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\AuthenticatableIdentity\AuthenticatableIdentityFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class ChangePasswordHandler
{
    public function __construct(
        private AuthenticatableIdentityFinderInterface $authenticatableIdentityFinder,
        private PasswordCredentialRepositoryInterface $repository,
        private PasswordPolicyInterface $policy,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AuthenticatableIdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws PasswordCredentialNotFoundException
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws SamePasswordException
     */
    public function __invoke(ChangePassword $command): void
    {
        if (!$this->authenticatableIdentityFinder->ofIdentityId($command->identityId)->authenticatable) {
            throw IdentityNotAuthenticatableException::forIdentity($command->identityId);
        }

        $passwordCredential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $passwordCredential->change(Password::fromString($command->password), $this->policy, $this->hasher, $this->clock->now());

        $this->repository->save($passwordCredential);
    }
}
