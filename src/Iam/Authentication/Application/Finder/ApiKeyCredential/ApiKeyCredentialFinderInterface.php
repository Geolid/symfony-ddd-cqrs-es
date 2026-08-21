<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\ApiKeyCredential;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface ApiKeyCredentialFinderInterface extends FinderInterface
{
    /**
     * @throws ApiKeyCredentialResultNotFoundException
     */
    public function ofKeyId(string $keyId): ApiKeyCredentialResult;
}
