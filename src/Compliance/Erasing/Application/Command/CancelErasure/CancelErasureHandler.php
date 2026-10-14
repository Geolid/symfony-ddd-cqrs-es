<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Application\ErasingUniqueKey;
use Compliance\Erasing\Application\Finder\Erasure\ErasureFinderInterface;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class CancelErasureHandler
{
    public function __construct(
        private ErasureRepositoryInterface $repository,
        private ErasureFinderInterface $finder,
        private UniquenessRegistryInterface $uniqueness,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ErasureNotFoundException
     * @throws ErasureAlreadyExistsException
     */
    public function __invoke(CancelErasure $command): void
    {
        $open = $this->finder->requestedOfIdentityOrNull($command->identityId) ?? throw ErasureNotFoundException::forIdentity($command->identityId);
        $erasure = $this->repository->load(ErasureId::fromString($open->id));
        $erasure->cancel($this->clock->now());

        $this->repository->save($erasure);

        if ($erasure->state->isCancelled()) {
            $this->uniqueness->release(UniqueKey::for(ErasingUniqueKey::IDENTITY), $erasure->id->toString());
        }
    }
}
