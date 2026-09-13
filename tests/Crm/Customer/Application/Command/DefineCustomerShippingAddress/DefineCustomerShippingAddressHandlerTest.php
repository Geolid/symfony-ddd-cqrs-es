<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\DefineCustomerShippingAddress;

use Crm\Customer\Application\Command\DefineCustomerShippingAddress\DefineCustomerShippingAddress;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Crm\Tests\Customer\Support\PostalAddressResultMapper;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineCustomerShippingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);
        $shippingAddress = PostalAddressMapper::toArray(CustomerBuilder::sample('shippingAddress'));

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
            PostalAddressMapper::toArray(CustomerBuilder::sample('shippingAddress')),
        ));
    }
}
