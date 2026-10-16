<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Psr\Clock\ClockInterface;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class ListTrustedDevicesByIdentityHandler
{
    public function __construct(
        private TrustedDeviceFinderInterface $trustedDeviceFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return list<TrustedDeviceResult>
     */
    public function __invoke(ListTrustedDevicesByIdentity $query): array
    {
        return [...$this->trustedDeviceFinder->activeByIdentity($query->identityId, $this->clock->now())];
    }
}
