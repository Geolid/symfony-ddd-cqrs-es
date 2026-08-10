<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
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
        $identity = Identity::register(
            id: IdentityId::fromString($command->id),
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($identity);
    }
}
