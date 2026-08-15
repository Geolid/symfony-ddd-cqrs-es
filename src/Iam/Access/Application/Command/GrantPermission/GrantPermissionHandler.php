<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\GrantPermission;

use Iam\Access\Domain\Exception\GrantNotFoundException;
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

    /**
     * @throws GrantNotFoundException
     */
    public function __invoke(GrantPermission $command): void
    {
        $permission = Permission::fromString($command->permission);
        $id = GrantId::forIdentityAndPermission($command->identityId, $permission->toString());

        if ($this->repository->has($id)) {
            $grant = $this->repository->load($id);
            $grant->reactivate($this->clock->now());
        } else {
            $grant = Grant::grant(
                id: $id,
                identityId: $command->identityId,
                permission: $permission,
                grantedAt: $this->clock->now(),
            );
        }

        $this->repository->save($grant);
    }
}
