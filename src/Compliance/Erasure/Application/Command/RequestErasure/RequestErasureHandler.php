<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\RequestErasure;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RequestErasureHandler
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
    public function __invoke(RequestErasure $command): void
    {
        $subject = $this->repository->load(SubjectId::fromString($command->subjectId));
        $subject->requestErasure($this->clock->now());
        $this->repository->save($subject);
    }
}
