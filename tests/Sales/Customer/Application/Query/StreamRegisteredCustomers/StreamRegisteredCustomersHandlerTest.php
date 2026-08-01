<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Query\StreamRegisteredCustomers;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Customer\Application\Query\StreamRegisteredCustomers\StreamRegisteredCustomers;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class StreamRegisteredCustomersHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsRegisteredCustomersLeavingOutTheErasedOnes(): void
    {
        // Given
        $registered = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();
        $this->store($registered);
        $this->store(CustomerTestFactory::new()->erased()->create());

        // When
        $result = $this->ask(new StreamRegisteredCustomers());

        // Then
        self::assertCount(1, $result);
        self::assertSame([$registered->id()->toString()], array_map(
            static fn (CustomerResult $customer): string => $customer->id,
            iterator_to_array($result),
        ));
    }
}
