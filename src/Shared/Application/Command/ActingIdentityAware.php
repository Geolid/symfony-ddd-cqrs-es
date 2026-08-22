<?php

declare(strict_types=1);

namespace Shared\Application\Command;

interface ActingIdentityAware
{
    public function actingIdentityId(): string;
}
