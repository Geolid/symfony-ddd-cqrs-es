<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RegisterIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RegisterIdentity $command): void
    {
        $this->repository->save(Identity::register(
            IdentityId::fromString($command->id),
            $this->clock->now(),
        ));
    }
}
