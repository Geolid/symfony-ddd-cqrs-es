<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\ApiKeyCredential;

use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;

interface ApiKeyCredentialFinderInterface
{
    /**
     * @throws ApiKeyCredentialResultNotFoundException
     */
    public function ofKeyId(string $keyId): ApiKeyCredentialResult;
}
