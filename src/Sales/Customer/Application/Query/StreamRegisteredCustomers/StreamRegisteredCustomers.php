<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Query\StreamRegisteredCustomers;

use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Shared\Application\Query\QueryInterface;
use Shared\Application\Query\Result\StreamResult;

/**
 * @implements QueryInterface<StreamResult<CustomerResult>>
 */
final readonly class StreamRegisteredCustomers implements QueryInterface
{
}
