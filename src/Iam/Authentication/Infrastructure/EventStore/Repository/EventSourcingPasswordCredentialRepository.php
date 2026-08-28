<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore\Repository;

use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EventSourcingPasswordCredentialRepository implements PasswordCredentialRepositoryInterface
{
    /**
     * @param Repository<PasswordCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.password_credential.repository')]
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
        try {
            $this->repository->save($passwordCredential);
        } catch (AggregateAlreadyExists) {
            throw PasswordCredentialAlreadyExistsException::forId($passwordCredential->id->toString());
        }
    }
}
