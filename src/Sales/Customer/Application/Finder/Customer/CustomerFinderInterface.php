<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<CustomerResult>
 */
interface CustomerFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws CustomerResultNotFoundException
     */
    public function ofId(string $id): CustomerResult;
}
