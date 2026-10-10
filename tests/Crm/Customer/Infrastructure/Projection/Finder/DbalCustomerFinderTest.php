<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Projection\Finder;

use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Crm\Tests\Customer\Support\PostalAddressResultMapper;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
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
    public function itFinds(): void
    {
        // Given
        $other = CustomerFactory::new()->create();
        $customer = CustomerFactory::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $this->store($other, $customer);

        // When
        $found = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertNotNull($found);
        self::assertSame($customer->id->toString(), $found->id);
        self::assertSameDate($customer->registeredAt, $found->registeredAt);
        self::assertNotNull($found->shippingAddress);
        \assert(null !== $customer->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->shippingAddress), PostalAddressResultMapper::toArray($found->shippingAddress));
        self::assertNotNull($found->billingAddress);
        \assert(null !== $customer->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->billingAddress), PostalAddressResultMapper::toArray($found->billingAddress));
        self::assertSame(ErasureStatus::RETAINED, $found->erasureStatus);
    }

    #[Test]
    public function itFindsNothing(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // When
        $result = $this->finder->ofIdOrNull($id);

        // Then
        self::assertNull($result);
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $customer = CustomerFactory::new()->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertNotNull($result);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }
}
