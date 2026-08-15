<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface IdentityFinderInterface extends FinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $id): IdentityResult;
}
