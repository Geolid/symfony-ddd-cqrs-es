<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetApiKeyCredentialByKeyId;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetApiKeyCredentialByKeyIdHandler
{
    public function __construct(private ApiKeyCredentialFinderInterface $apiKeyCredentialFinder)
    {
    }

    /**
     * @throws ApiKeyCredentialResultNotFoundException
     */
    public function __invoke(GetApiKeyCredentialByKeyId $query): ApiKeyCredentialResult
    {
        return $this->apiKeyCredentialFinder->ofKeyId($query->keyId);
    }
}
