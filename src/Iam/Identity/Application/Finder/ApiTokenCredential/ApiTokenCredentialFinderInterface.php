<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<ApiTokenCredentialResult>
 */
interface ApiTokenCredentialFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws ApiTokenCredentialResultNotFoundException
     */
    public function ofIdentifier(string $identifier): ApiTokenCredentialResult;

    public function byIdentity(string $identityId): static;

    public function active(): static;
}
