<?php

declare(strict_types=1);

namespace Compliance\Erasure\Infrastructure\EventStore;

use Compliance\Erasure\Domain\Exception\SubjectAlreadyExistsException;
use Compliance\Erasure\Domain\Exception\SubjectNotFoundException;
use Compliance\Erasure\Domain\Repository\SubjectRepositoryInterface;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelSubjectRepository implements SubjectRepositoryInterface
{
    /**
     * @param Repository<Subject> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.compliance.erasure.subject.repository')]
        private Repository $repository,
    ) {
    }

    public function has(SubjectId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(SubjectId $id): Subject
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw SubjectNotFoundException::forId($id->toString());
        }
    }

    public function save(Subject $subject): void
    {
        try {
            $this->repository->save($subject);
        } catch (AggregateAlreadyExists) {
            throw SubjectAlreadyExistsException::forId($subject->id->toString());
        }
    }
}
