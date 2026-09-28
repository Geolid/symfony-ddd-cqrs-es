<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetDeviceTrustByIdentity;

use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustFinderInterface;
use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetDeviceTrustByIdentityHandler
{
    public function __construct(private DeviceTrustFinderInterface $deviceTrustFinder)
    {
    }

    public function __invoke(GetDeviceTrustByIdentity $query): ?DeviceTrustResult
    {
        return $this->deviceTrustFinder->ofIdentityOrNull($query->identityId);
    }
}
