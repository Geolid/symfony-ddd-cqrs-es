<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\ApiTokenCredential;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<ApiTokenCredentialResult>
 */
interface ApiTokenCredentialFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofIdentifier(string $identifier): ApiTokenCredentialResult;

    public function byIdentity(string $identityId): static;

    public function active(): static;
}
