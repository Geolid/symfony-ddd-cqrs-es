<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<CustomerResult>
 */
interface CustomerFinderInterface extends PaginatedFinderInterface
{
    public function getByIdentityId(string $identityId): ?CustomerResult;

    public function withoutErased(): static;
}
