<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\LiftHold;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class LiftHoldHandler
{
    public function __construct(
        private SubjectRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws SubjectNotFoundException
     * @throws SubjectAlreadyExistsException
     */
    public function __invoke(LiftHold $command): void
    {
        $id = SubjectId::fromString($command->subjectId);

        if (!$this->repository->has($id)) {
            return;
        }

        $subject = $this->repository->load($id);
        $subject->liftHold(HoldReference::for($command->sourceType, $command->sourceId), $this->clock->now());
        $this->repository->save($subject);
    }
}
