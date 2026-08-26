<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Shared\Application\Finder\IterableFinderInterface;
use Shared\Application\Finder\PaginatableFinderInterface;

/**
 * @extends IterableFinderInterface<IdentityResult>
 * @extends PaginatableFinderInterface<IdentityResult>
 */
interface IdentityFinderInterface extends IterableFinderInterface, PaginatableFinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $id): IdentityResult;
}
