<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\AggregateNotFoundException;

#[AsCommandHandler]
final readonly class ReactivateIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AggregateNotFoundException
     * @throws IdentityAlreadyErasedException
     */
    public function __invoke(ReactivateIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->reactivate($this->clock->now());

        $this->repository->save($identity);
    }
}
