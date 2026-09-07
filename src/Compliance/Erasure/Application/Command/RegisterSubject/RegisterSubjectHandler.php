<?php

declare(strict_types=1);

namespace Compliance\Erasure\Application\Command\RegisterSubject;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RegisterSubjectHandler
{
    public function __construct(private SubjectRepositoryInterface $repository)
    {
    }

    public function __invoke(RegisterSubject $command): void
    {
        $id = SubjectId::fromString($command->id);
        $subject = Subject::register($id, $command->registeredAt);

        try {
            $this->repository->save($subject);
        } catch (SubjectAlreadyExistsException) {
            return;
        }
    }
}
