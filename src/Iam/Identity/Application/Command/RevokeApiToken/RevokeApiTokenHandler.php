<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiToken;

use Iam\Identity\Domain\ApiTokenId;
use Iam\Identity\Domain\Exception\ApiTokenAlreadyRevokedException;
use Iam\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RevokeApiTokenHandler
{
    public function __construct(
        private ApiTokenRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiTokenAlreadyRevokedException
     */
    public function __invoke(RevokeApiToken $command): void
    {
        $apiToken = $this->repository->load(ApiTokenId::fromString($command->id));
        $apiToken->revoke($this->clock->now());

        $this->repository->save($apiToken);
    }
}
