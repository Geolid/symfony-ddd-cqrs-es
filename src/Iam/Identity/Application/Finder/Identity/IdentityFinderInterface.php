<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;
use Shared\Application\Finder\PaginableFinderInterface;

/**
 * @extends CollectionFinderInterface<IdentityResult>
 * @extends PaginableFinderInterface<IdentityResult>
 */
interface IdentityFinderInterface extends CollectionFinderInterface, PaginableFinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $id): IdentityResult;
}
