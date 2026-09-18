<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RequestPasswordReset;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestPasswordResetHandler
{
    public function __construct(
        private IdentityFinderInterface $identityFinder,
        private PasswordCredentialRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws PasswordCredentialNotFoundException
     * @throws PasswordResetRequestedTooRecentlyException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(RequestPasswordReset $command): void
    {
        $identity = $this->identityFinder->ofId($command->identityId);

        if (!$identity->isAuthenticatable()) {
            throw IdentityNotAuthenticatableException::forIdentity($command->identityId);
        }

        $credential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $credential->requestReset($this->clock->now());

        $this->repository->save($credential);
    }
}
