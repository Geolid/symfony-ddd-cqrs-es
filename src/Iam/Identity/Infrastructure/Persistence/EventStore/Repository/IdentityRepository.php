<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class IdentityRepository implements IdentityRepositoryInterface
{
    /**
     * @param Repository<Identity> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.identity.identity.repository')]
        private Repository $repository,
    ) {
    }

    public function has(IdentityId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(IdentityId $id): Identity
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw IdentityNotFoundException::forId($id);
        }
    }

    public function save(Identity $identity): void
    {
        $this->repository->save($identity);
    }
}
