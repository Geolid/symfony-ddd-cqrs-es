<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\RevokePermission;

use Iam\Access\Domain\Repository\GrantRepositoryInterface;
use Iam\Access\Domain\ValueObject\GrantId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class RevokePermissionHandler
{
    public function __construct(
        private GrantRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     */
    public function __invoke(RevokePermission $command): void
    {
        $grant = $this->repository->load(GrantId::fromString($command->id));
        $grant->revoke($this->clock->now());

        $this->repository->save($grant);
    }
}
