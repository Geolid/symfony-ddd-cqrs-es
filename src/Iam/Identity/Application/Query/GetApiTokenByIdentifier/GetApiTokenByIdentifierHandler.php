<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetApiTokenByIdentifier;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiToken\ApiTokenCredentialResult;
use Iam\Identity\Application\Finder\ApiToken\ApiTokenFinderInterface;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetApiTokenByIdentifierHandler
{
    public function __construct(private ApiTokenFinderInterface $apiTokenFinder)
    {
    }

    /**
     * @throws ApiTokenCredentialResultNotFoundException
     */
    public function __invoke(GetApiTokenByIdentifier $query): ApiTokenCredentialResult
    {
        foreach ($this->apiTokenFinder as $apiToken) {
            if ($apiToken->identifier === $query->identifier) {
                return $apiToken;
            }
        }

        throw ApiTokenCredentialResultNotFoundException::forIdentifier($query->identifier);
    }
}
