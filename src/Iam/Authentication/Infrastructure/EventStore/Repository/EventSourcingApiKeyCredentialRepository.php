<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore\Repository;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EventSourcingApiKeyCredentialRepository implements ApiKeyCredentialRepositoryInterface
{
    /**
     * @param Repository<ApiKeyCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.api_key_credential.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ApiKeyCredentialId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ApiKeyCredentialId $id): ApiKeyCredential
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ApiKeyCredentialNotFoundException::forId($id->toString());
        }
    }

    public function save(ApiKeyCredential $apiKeyCredential): void
    {
        try {
            $this->repository->save($apiKeyCredential);
        } catch (AggregateAlreadyExists) {
            throw ApiKeyCredentialAlreadyExistsException::forId($apiKeyCredential->id->toString());
        }
    }
}
