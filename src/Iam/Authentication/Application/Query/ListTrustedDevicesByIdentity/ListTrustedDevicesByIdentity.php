<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\ListTrustedDevicesByIdentity;

use Iam\Authentication\Application\Finder\TrustedDevice\TrustedDeviceResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<TrustedDeviceResult>>
 */
final readonly class ListTrustedDevicesByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
