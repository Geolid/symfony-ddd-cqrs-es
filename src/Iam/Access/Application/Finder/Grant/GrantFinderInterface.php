<?php

declare(strict_types=1);

namespace Iam\Access\Application\Finder\Grant;

use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<GrantResult>
 */
interface GrantFinderInterface extends CollectionFinderInterface
{
    public function byIdentity(string ...$identityIds): static;
}
