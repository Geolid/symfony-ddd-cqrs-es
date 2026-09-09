<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Erasing\Domain\ValueObject\ErasureUniqueKey;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class CancelErasureHandler
{
    public function __construct(
        private ErasureRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ErasureNotFoundException
     * @throws ErasureAlreadyExistsException
     */
    public function __invoke(CancelErasure $command): void
    {
        $erasure = $this->repository->load(ErasureId::fromString($command->id));
        $erasure->cancel($this->clock->now());

        $this->repository->save($erasure);

        if ($erasure->state->isCancelled()) {
            $this->uniqueValues->release(UniqueKey::for(ErasureUniqueKey::IDENTITY), $erasure->id->toString());
        }
    }
}
