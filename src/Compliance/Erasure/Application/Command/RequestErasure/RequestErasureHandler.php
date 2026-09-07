<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\RequestErasure;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\Subject;
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
        $id = SubjectId::fromString($command->subjectId);
        $now = $this->clock->now();

        if ($this->repository->has($id)) {
            $subject = $this->repository->load($id);
            $subject->requestErasure($now);
        } else {
            $subject = Subject::request($id, $now);
        }

        $this->repository->save($subject);
    }
}
