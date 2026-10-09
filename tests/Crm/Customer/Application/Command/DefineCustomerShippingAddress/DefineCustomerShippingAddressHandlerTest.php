<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\DefineCustomerShippingAddress;

use Crm\Customer\Application\Command\DefineCustomerShippingAddress\DefineCustomerShippingAddress;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Crm\Tests\Customer\Support\PostalAddressResultMapper;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineCustomerShippingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $customer = CustomerFactory::new()->create();
        $this->store($customer);
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());

        // When
        $this->dispatch(new DefineCustomerShippingAddress($customer->id->toString(), $shippingAddress));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertNotNull($result->shippingAddress);
        self::assertSame($shippingAddress, PostalAddressResultMapper::toArray($result->shippingAddress));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new DefineCustomerShippingAddress(
            Uuid::uuid7()->toString(),
            PostalAddressMapper::toArray(PostalAddressFactory::new()->create()),
        ));
    }
}
