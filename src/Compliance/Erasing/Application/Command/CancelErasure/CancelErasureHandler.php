<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\CancelErasure;

use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class CancelErasureHandler
{
    public function __construct(
        private ErasureRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ErasureNotFoundException
     * @throws ErasureAlreadyExistsException
     */
    public function __invoke(CancelErasure $command): void
    {
        $erasure = $this->repository->load(ErasureId::forIdentity($command->identityId));
        $erasure->cancel($this->clock->now());
        $this->repository->save($erasure);
    }
}
