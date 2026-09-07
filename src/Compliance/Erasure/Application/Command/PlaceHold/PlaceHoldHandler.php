<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\PlaceHold;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class PlaceHoldHandler
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
    public function __invoke(PlaceHold $command): void
    {
        $id = SubjectId::fromString($command->subjectId);
        $reference = HoldReference::for($command->sourceType, $command->sourceId);
        $now = $this->clock->now();

        if ($this->repository->has($id)) {
            $subject = $this->repository->load($id);
            $subject->placeHold($reference, $now);
        } else {
            $subject = Subject::place($id, $reference, $now);
        }

        $this->repository->save($subject);
    }
}
