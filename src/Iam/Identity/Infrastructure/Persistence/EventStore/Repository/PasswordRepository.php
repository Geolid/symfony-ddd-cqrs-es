<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\PasswordNotFoundException;
use Iam\Identity\Domain\Password;
use Iam\Identity\Domain\PasswordId;
use Iam\Identity\Domain\Repository\PasswordRepositoryInterface;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PasswordRepository implements PasswordRepositoryInterface
{
    /**
     * @param Repository<Password> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.identity.password.repository')]
        private Repository $repository,
    ) {
    }

    public function has(PasswordId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(PasswordId $id): Password
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw PasswordNotFoundException::forId($id);
        }
    }

    public function save(Password $password): void
    {
        $this->repository->save($password);
    }
}
