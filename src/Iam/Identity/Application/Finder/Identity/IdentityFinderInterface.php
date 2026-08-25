<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<IdentityResult>
 */
interface IdentityFinderInterface extends PaginatedCollectionFinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $id): IdentityResult;
}
