<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Finder;

use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Tests\Checkout\Support\PostalAddressResultMapper;
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
        $other = CustomerBuilder::new()->create();
        $builder = CustomerBuilder::new()->shippingAddressDefined()->billingAddressDefined();
        $customer = $builder->create();
        $this->store($other, $customer);

        // When
        $found = $this->finder->ofIdOrNull($customer->id->toString());
        $notFound = $this->finder->ofIdOrNull(Uuid::uuid7()->toString());

        // Then
        self::assertNotNull($found);
        self::assertSame($customer->id->toString(), $found->id);
        self::assertNotNull($found->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), PostalAddressResultMapper::toArray($found->shippingAddress));
        self::assertNotNull($found->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), PostalAddressResultMapper::toArray($found->billingAddress));
        self::assertSame(ErasureStatus::RETAINED, $found->erasureStatus);
        self::assertNull($notFound);
    }

    #[Test]
    public function itFindsWithNoAddress(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);

        // When
        $result = $this->finder->ofIdOrNull($customer->id->toString());

        // Then
        self::assertNotNull($result);
        self::assertNull($result->shippingAddress);
        self::assertNull($result->billingAddress);
    }
}
