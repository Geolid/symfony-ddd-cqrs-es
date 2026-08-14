<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Application\Exception\ResultNotFoundException;
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
    public function itGetsById(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();

        // When
        $result = $this->finder->ofId($customer->id()->toString());

        // Then
        self::assertSame($customer->id()->toString(), $result->id);
        self::assertSame('buyer@example.com', $result->email);
        self::assertNull($result->erasedAt);
    }

    #[Test]
    public function itThrowsOnAnUnknown(): void
    {
        // Then
        $this->expectException(ResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
