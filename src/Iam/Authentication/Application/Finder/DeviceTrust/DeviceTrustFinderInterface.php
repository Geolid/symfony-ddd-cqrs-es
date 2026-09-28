<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\DeviceTrust;

interface DeviceTrustFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?DeviceTrustResult;
}
