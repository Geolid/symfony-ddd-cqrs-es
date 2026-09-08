<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\CancelIdentityErasure;

use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelIdentityErasureHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(CancelIdentityErasure $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->cancelErasure($this->clock->now());
        $this->repository->save($identity);
    }
}
