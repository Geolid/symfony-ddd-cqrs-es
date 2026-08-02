<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\ApiTokenCredential;
use Iam\Identity\Domain\ApiTokenCredentialId;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ApiTokenCredentialRepository implements ApiTokenCredentialRepositoryInterface
{
    /**
     * @param Repository<ApiTokenCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.identity.api_token_credential.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ApiTokenCredentialId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ApiTokenCredentialId $id): ApiTokenCredential
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ApiTokenCredentialNotFoundException::forId($id);
        }
    }

    public function save(ApiTokenCredential $apiTokenCredential): void
    {
        $this->repository->save($apiTokenCredential);
    }
}
