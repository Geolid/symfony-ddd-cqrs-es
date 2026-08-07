<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface ApiTokenCredentialFinderInterface extends FinderInterface
{
    /**
     * @throws ApiTokenCredentialResultNotFoundException
     */
    public function ofIdentifier(string $identifier): ApiTokenCredentialResult;
}
