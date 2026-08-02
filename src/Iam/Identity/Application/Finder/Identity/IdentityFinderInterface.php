<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

interface IdentityFinderInterface
{
    public function getById(string $id): ?IdentityResult;
}
