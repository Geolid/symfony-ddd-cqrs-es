<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PasswordCredentialRepository implements PasswordCredentialRepositoryInterface
{
    /**
     * @param Repository<PasswordCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.identity.password_credential.repository')]
        private Repository $repository,
    ) {
    }

    public function has(PasswordCredentialId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(PasswordCredentialId $id): PasswordCredential
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw PasswordCredentialNotFoundException::forId($id->toString());
        }
    }

    public function save(PasswordCredential $passwordCredential): void
    {
        $this->repository->save($passwordCredential);
    }
}
