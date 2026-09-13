<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\DefineCustomerBillingAddress;

use Crm\Customer\Application\Command\DefineCustomerBillingAddress\DefineCustomerBillingAddress;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Crm\Tests\Customer\Support\PostalAddressResultMapper;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Support\TestCase\AbstractIntegrationTestCase;

final class DefineCustomerBillingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDefines(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);
        $billingAddress = PostalAddressMapper::toArray(CustomerBuilder::sample('billingAddress'));

        // When
        $this->dispatch(new DefineCustomerBillingAddress($customer->id->toString(), $billingAddress));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString());
        self::assertNotNull($result);
        self::assertNotNull($result->billingAddress);
        self::assertSame($billingAddress, PostalAddressResultMapper::toArray($result->billingAddress));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new DefineCustomerBillingAddress(
            Uuid::uuid7()->toString(),
            PostalAddressMapper::toArray(CustomerBuilder::sample('billingAddress')),
        ));
    }
}
