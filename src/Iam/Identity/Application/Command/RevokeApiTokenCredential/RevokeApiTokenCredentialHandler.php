<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiTokenCredential;

use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RevokeApiTokenCredentialHandler
{
    public function __construct(
        private ApiTokenCredentialRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiTokenCredentialNotFoundException
     * @throws ApiTokenCredentialAlreadyRevokedException
     */
    public function __invoke(RevokeApiTokenCredential $command): void
    {
        $apiTokenCredential = $this->repository->load(ApiTokenCredentialId::fromString($command->id));
        $apiTokenCredential->revoke($this->clock->now());

        $this->repository->save($apiTokenCredential);
    }
}
