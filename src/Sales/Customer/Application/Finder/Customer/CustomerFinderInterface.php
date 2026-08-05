<?php

declare(strict_types=1);

namespace Sales\Customer\Application\Finder\Customer;

use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<CustomerResult>
 */
interface CustomerFinderInterface extends PaginatedCollectionFinderInterface
{
    public function ofIdentityId(string $identityId): ?CustomerResult;

    public function withoutErased(): static;
}
