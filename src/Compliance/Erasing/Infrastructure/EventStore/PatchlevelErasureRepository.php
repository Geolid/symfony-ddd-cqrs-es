<?php

declare(strict_types=1);

namespace Compliance\Erasing\Infrastructure\EventStore;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Exception\ErasureAlreadyExistsException;
use Compliance\Erasing\Domain\Exception\ErasureNotFoundException;
use Compliance\Erasing\Domain\Repository\ErasureRepositoryInterface;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelErasureRepository implements ErasureRepositoryInterface
{
    /**
     * @param Repository<Erasure> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.compliance.erasing.erasure.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ErasureId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ErasureId $id): Erasure
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ErasureNotFoundException::forId($id->toString());
        }
    }

    public function save(Erasure $erasure): void
    {
        try {
            $this->repository->save($erasure);
        } catch (AggregateAlreadyExists) {
            throw ErasureAlreadyExistsException::forId($erasure->id->toString());
        }
    }
}
