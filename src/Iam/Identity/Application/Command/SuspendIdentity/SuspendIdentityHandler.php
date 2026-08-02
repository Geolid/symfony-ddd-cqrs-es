<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class SuspendIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadySuspendedException
     */
    public function __invoke(SuspendIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->suspend($this->clock->now());

        $this->repository->save($identity);
    }
}
