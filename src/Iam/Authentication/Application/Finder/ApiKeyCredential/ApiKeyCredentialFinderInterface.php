<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\ApiKeyCredential;

use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<ApiKeyCredentialResult>
 */
interface ApiKeyCredentialFinderInterface extends IterableFinderInterface
{
    /**
     * @throws ApiKeyCredentialResultNotFoundException
     */
    public function ofKeyId(string $keyId): ApiKeyCredentialResult;

    public function byIdentity(string $identityId): static;
}
