<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCustomerFinderTest extends AbstractIntegrationTestCase
{
    private CustomerFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CustomerFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = CustomerBuilder::new()->withEmail('other@example.com')->create();
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com')->create();
        $this->store($other, $customer);

        // When
        $result = $this->finder->ofId($customer->id->toString());

        // Then
        self::assertSame($customer->id->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->email);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
