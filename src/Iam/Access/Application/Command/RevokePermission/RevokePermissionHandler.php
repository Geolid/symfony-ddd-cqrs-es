<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\RevokePermission;

use Iam\Access\Domain\Exception\PermissionAlreadyRevokedException;
use Iam\Access\Domain\GrantId;
use Iam\Access\Domain\Repository\GrantRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RevokePermissionHandler
{
    public function __construct(
        private GrantRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PermissionAlreadyRevokedException
     */
    public function __invoke(RevokePermission $command): void
    {
        $grant = $this->repository->load(GrantId::fromString($command->id));
        $grant->revoke($this->clock->now());

        $this->repository->save($grant);
    }
}
