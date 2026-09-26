<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceFinderInterface;
use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class ListTrustedDevicesByIdentityHandler
{
    public function __construct(private TrustedDeviceFinderInterface $trustedDeviceFinder)
    {
    }

    /**
     * @return list<TrustedDeviceResult>
     */
    public function __invoke(ListTrustedDevicesByIdentity $query): array
    {
        return iterator_to_array($this->trustedDeviceFinder->activeByIdentity($query->identityId), false);
    }
}
