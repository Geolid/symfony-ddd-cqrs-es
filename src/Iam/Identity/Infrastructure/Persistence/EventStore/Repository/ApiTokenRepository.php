<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Persistence\EventStore\Repository;

use Iam\Identity\Domain\ApiToken;
use Iam\Identity\Domain\ApiTokenId;
use Iam\Identity\Domain\Exception\ApiTokenNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenRepositoryInterface;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ApiTokenRepository implements ApiTokenRepositoryInterface
{
    /**
     * @param Repository<ApiToken> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.identity.api_token.repository')]
        private Repository $repository,
    ) {
    }

    public function has(ApiTokenId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(ApiTokenId $id): ApiToken
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw ApiTokenNotFoundException::forId($id);
        }
    }

    public function save(ApiToken $apiToken): void
    {
        $this->repository->save($apiToken);
    }
}
