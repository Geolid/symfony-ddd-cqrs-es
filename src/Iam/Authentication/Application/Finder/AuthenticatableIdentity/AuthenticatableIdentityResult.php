<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\AuthenticatableIdentity;

use Shared\Application\Result\ResultInterface;

final readonly class AuthenticatableIdentityResult implements ResultInterface
{
    public function __construct(
        public string $identityId,
        public bool $authenticatable,
    ) {
    }
}
