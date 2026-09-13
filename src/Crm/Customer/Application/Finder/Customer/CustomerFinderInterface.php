<?php

declare(strict_types=1);

namespace Crm\Customer\Application\Finder\Customer;

interface CustomerFinderInterface
{
    public function ofIdOrNull(string $id): ?CustomerResult;
}
