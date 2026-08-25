<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReactivateIdentity;

use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ReactivateIdentityHandler
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
    public function __invoke(ReactivateIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->reactivate(Reason::fromString($command->reason), $this->clock->now());

        $this->repository->save($identity);
    }
}
