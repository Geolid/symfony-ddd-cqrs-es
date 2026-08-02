<?php

declare(strict_types=1);

namespace Iam\Access\Infrastructure\Persistence\EventStore\Repository;

use Iam\Access\Domain\Exception\GrantNotFoundException;
use Iam\Access\Domain\Grant;
use Iam\Access\Domain\GrantId;
use Iam\Access\Domain\Repository\GrantRepositoryInterface;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GrantRepository implements GrantRepositoryInterface
{
    /**
     * @param Repository<Grant> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.access.grant.repository')]
        private Repository $repository,
    ) {
    }

    public function has(GrantId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(GrantId $id): Grant
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw GrantNotFoundException::forId($id);
        }
    }

    public function save(Grant $grant): void
    {
        $this->repository->save($grant);
    }
}
