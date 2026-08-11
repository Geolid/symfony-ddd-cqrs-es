<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface CustomerFinderInterface extends FinderInterface
{
    /**
     * @throws CustomerResultNotFoundException
     */
    public function ofId(string $id): CustomerResult;
}
