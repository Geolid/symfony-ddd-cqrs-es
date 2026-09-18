<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SuspendIdentity;

use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class SuspendIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(SuspendIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->suspend(Reason::fromString($command->reason), $this->clock->now());

        $this->repository->save($identity);
    }
}
