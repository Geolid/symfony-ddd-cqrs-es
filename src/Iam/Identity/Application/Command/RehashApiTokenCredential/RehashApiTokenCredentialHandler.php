<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RehashApiTokenCredential;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RehashApiTokenCredentialHandler
{
    public function __construct(
        private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder,
        private ApiTokenCredentialRepositoryInterface $repository,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiTokenCredentialResultNotFoundException
     * @throws ApiTokenCredentialNotFoundException
     */
    public function __invoke(RehashApiTokenCredential $command): void
    {
        $current = $this->apiTokenCredentialFinder->ofIdentifier($command->identifier);

        if (!$this->hasher->needsRehash($current->hash)) {
            return;
        }

        $apiTokenCredential = $this->repository->load(ApiTokenCredentialId::fromString($current->id));
        $apiTokenCredential->rehash($command->plainSecret, $this->hasher, $this->clock->now());

        $this->repository->save($apiTokenCredential);
    }
}
