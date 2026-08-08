<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\GetCustomerByIdentity;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<CustomerResult>
 */
final readonly class GetCustomerByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
