<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\PlaceSubjectErasureHold;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class PlaceSubjectErasureHoldHandler
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
    public function __invoke(PlaceSubjectErasureHold $command): void
    {
        $subject = $this->repository->load(SubjectId::fromString($command->subjectId));
        $subject->placeErasureHold(ErasureHoldReference::for($command->sourceType, $command->sourceId), $this->clock->now());
        $this->repository->save($subject);
    }
}
