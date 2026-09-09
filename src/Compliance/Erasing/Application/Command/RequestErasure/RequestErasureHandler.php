<?php

declare(strict_types=1);

namespace Compliance\Erasing\Application\Command\RequestErasure;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestErasureHandler
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
    public function __invoke(RequestErasure $command): void
    {
        $id = ErasureId::forIdentity($command->identityId);
        $now = $this->clock->now();

        if ($this->repository->has($id)) {
            $erasure = $this->repository->load($id);
            $erasure->reRequest($now);
        } else {
            $erasure = Erasure::request($id, $command->identityId, $now);
        }

        $this->repository->save($erasure);
    }
}
