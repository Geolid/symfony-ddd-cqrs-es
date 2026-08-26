<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Sales\Customer\Application\Exception\CustomerResultNotFoundException;

interface CustomerFinderInterface
{
    /**
     * @throws CustomerResultNotFoundException
     */
    public function ofId(string $id): CustomerResult;
}
