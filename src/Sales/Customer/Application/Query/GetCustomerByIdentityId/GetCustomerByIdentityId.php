<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\GetCustomerByIdentityId;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?CustomerResult>
 */
final readonly class GetCustomerByIdentityId implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
