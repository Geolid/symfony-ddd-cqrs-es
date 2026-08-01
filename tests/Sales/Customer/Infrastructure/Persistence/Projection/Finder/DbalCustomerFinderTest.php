<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalCustomerFinderTest extends AbstractIntegrationTestCase
{
    private CustomerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CustomerFinderInterface::class);
    }

    #[Test]
    public function itReadsACustomerAsItWasRegistered(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();
        $this->store($customer);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(CustomerResult::class, $result);
        self::assertSame($customer->id()->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->email);
        self::assertNull($result->erasedAt);
    }

    #[Test]
    public function itFiltersCustomersByErasure(): void
    {
        // Given
        $registered = CustomerTestFactory::new()->create();
        $this->store($registered);
        $this->store(CustomerTestFactory::new()->erased()->create());

        // When
        $results = iterator_to_array($this->finder->withoutErased());

        // Then
        self::assertSame(2, $this->finder->count());
        self::assertSame([$registered->id()->toString()], array_map(
            static fn (CustomerResult $customer): string => $customer->id,
            $results,
        ));
    }
}
