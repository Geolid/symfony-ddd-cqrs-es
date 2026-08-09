<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class EraseIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $identityRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     */
    public function __invoke(EraseIdentity $command): void
    {
        $identityId = IdentityId::fromString($command->id);
        $identity = $this->identityRepository->load($identityId);

        $identity->erase($this->clock->now());
        $this->identityRepository->save($identity);
    }
}
