<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface CustomerFinderInterface extends FinderInterface
{
    /**
     * @throws ResultNotFoundException
     */
    public function ofId(string $id): CustomerResult;
}
