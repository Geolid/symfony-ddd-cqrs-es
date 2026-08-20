<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\AuthenticatableIdentity;

use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface AuthenticatableIdentityFinderInterface extends FinderInterface
{
    /**
     * @throws AuthenticatableIdentityResultNotFoundException
     */
    public function ofIdentityId(string $identityId): AuthenticatableIdentityResult;
}
