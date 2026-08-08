<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Security;

interface IdentityAwareUserInterface
{
    public function identityId(): string;
}
