<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Query\ListCustomers;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Query\ListCustomers\ListCustomers;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class ListCustomersHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsCustomersErasedOnesIncluded(): void
    {
        // Given
        $this->store(CustomerTestFactory::new()->withEmail('buyer@example.com')->create());
        $this->store(CustomerTestFactory::new()->erased()->create());

        // When
        $result = $this->ask(new ListCustomers());

        // Then
        self::assertCount(2, $result->items);
        self::assertSame(2, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itPaginatesCustomers(): void
    {
        // Given
        $this->store(CustomerTestFactory::new()->create());
        $this->store(CustomerTestFactory::new()->create());

        // When
        $result = $this->ask(new ListCustomers(page: 2, itemsPerPage: 1));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame(2, $result->pagination->totalItems);
        self::assertSame(2, $result->pagination->currentPage);
        self::assertSame(2, $result->pagination->lastPage);
    }
}
