<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Shared\Application\Finder\FinderInterface;

interface IdentityFinderInterface extends FinderInterface
{
    public function ofId(string $id): ?IdentityResult;
}
