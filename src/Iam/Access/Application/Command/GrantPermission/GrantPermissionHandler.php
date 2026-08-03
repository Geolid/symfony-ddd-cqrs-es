<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\GrantPermission;

use Iam\Access\Domain\Grant;
use Iam\Access\Domain\Repository\GrantRepositoryInterface;
use Iam\Access\Domain\ValueObject\GrantId;
use Iam\Access\Domain\ValueObject\Permission;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class GrantPermissionHandler
{
    public function __construct(
        private GrantRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(GrantPermission $command): void
    {
        $this->repository->save(Grant::grant(
            GrantId::fromString($command->id),
            $command->identityId,
            Permission::fromString($command->permission),
            $this->clock->now(),
        ));
    }
}
