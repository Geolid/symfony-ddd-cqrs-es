<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Query\GetApiTokenCredentialByIdentifier;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetApiTokenCredentialByIdentifierHandler
{
    public function __construct(private ApiTokenCredentialFinderInterface $apiTokenCredentialFinder)
    {
    }

    /**
     * @throws ApiTokenCredentialResultNotFoundException
     */
    public function __invoke(GetApiTokenCredentialByIdentifier $query): ApiTokenCredentialResult
    {
        return $this->apiTokenCredentialFinder->ofIdentifier($query->identifier);
    }
}
