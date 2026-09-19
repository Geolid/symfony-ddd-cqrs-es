<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\Identity;

use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;

interface IdentityFinderInterface
{
    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofId(string $identityId): IdentityResult;

    /**
     * @throws IdentityResultNotFoundException
     */
    public function ofEmail(string $email): IdentityResult;
}
