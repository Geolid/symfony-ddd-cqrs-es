<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\ApiKeyCredential\Repository;

use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;

interface ApiKeyCredentialRepositoryInterface
{
    public function has(ApiKeyCredentialId $id): bool;

    /**
     * @throws ApiKeyCredentialNotFoundException
     */
    public function load(ApiKeyCredentialId $id): ApiKeyCredential;

    /**
     * @throws ApiKeyCredentialAlreadyExistsException
     */
    public function save(ApiKeyCredential $apiKeyCredential): void;
}
