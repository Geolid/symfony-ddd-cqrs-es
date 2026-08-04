<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class ReactivateIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityNotSuspendedException
     */
    public function __invoke(ReactivateIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->reactivate($this->clock->now());

        $this->repository->save($identity);
    }
}
