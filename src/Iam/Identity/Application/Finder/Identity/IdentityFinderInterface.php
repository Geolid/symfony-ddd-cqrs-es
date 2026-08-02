<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

interface IdentityFinderInterface
{
    public function ofId(string $id): ?IdentityResult;
}
