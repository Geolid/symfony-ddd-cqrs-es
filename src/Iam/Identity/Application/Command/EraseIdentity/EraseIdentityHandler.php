<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\EraseIdentity;

use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class EraseIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(EraseIdentity $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));

        $now = $this->clock->now();
        $identity->requestErasure($now);
        $identity->erase($now);
        $this->repository->save($identity);

        $this->uniqueness->release(UniqueKey::for(IdentityUniqueKey::EMAIL), $command->id);
    }
}
