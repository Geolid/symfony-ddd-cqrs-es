<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RehashPasswordCredential;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RehashPasswordCredentialHandler
{
    public function __construct(
        private PasswordCredentialFinderInterface $passwordCredentialFinder,
        private PasswordCredentialRepositoryInterface $repository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PasswordCredentialResultNotFoundException
     * @throws PasswordCredentialNotFoundException
     */
    public function __invoke(RehashPasswordCredential $command): void
    {
        $current = $this->passwordCredentialFinder->ofIdentityId($command->identityId);

        if (!$this->hasher->needsRehash($current->hash)) {
            return;
        }

        $passwordCredential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $passwordCredential->rehash($command->plainSecret, $this->hasher, $this->clock->now());

        $this->repository->save($passwordCredential);
    }
}
