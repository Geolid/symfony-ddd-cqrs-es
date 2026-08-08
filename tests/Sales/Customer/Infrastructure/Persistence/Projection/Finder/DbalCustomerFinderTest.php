<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
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
    public function itGetsACustomer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofId($customer->id()->toString());

        // Then
        self::assertSame($customer->id()->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->email);
        self::assertNull($result->erasedAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownCustomer(): void
    {
        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itListsCustomers(): void
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
}
