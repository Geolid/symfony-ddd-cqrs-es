<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RehashPassword;

use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RehashPasswordHandler
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordCredentialRepositoryInterface $repository,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     * @throws PasswordCredentialNotFoundException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(RehashPassword $command): void
    {
        $current = $this->passwordCredentialFinder->ofIdentityId($command->identityId);

        if (!$this->hasher->needsRehash($current->passwordHash)) {
            return;
        }

        $credential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $credential->rehash($command->password, $this->hasher, $this->clock->now());

        $this->repository->save($credential);
    }
}
