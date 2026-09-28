<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetDeviceTrustByIdentity;

use Iam\Authentication\Application\Finder\DeviceTrust\DeviceTrustResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<DeviceTrustResult>
 */
final readonly class GetDeviceTrustByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
