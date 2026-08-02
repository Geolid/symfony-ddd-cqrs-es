<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Query\GetCustomerByIdentityId;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Query\GetCustomerByIdentityId\GetCustomerByIdentityId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetCustomerByIdentityIdHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsACustomerByIdentityId(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->linkedToIdentity('identity-1')->create();
        $this->store($customer);
        $this->store(CustomerTestFactory::new()->create());

        // When
        $result = $this->ask(new GetCustomerByIdentityId('identity-1'));

        // Then
        self::assertNotNull($result);
        self::assertSame($customer->id()->toString(), $result->id);
    }

    #[Test]
    public function itReturnsNullWhenNoCustomerIsLinkedToTheIdentity(): void
    {
        // Given
        $this->store(CustomerTestFactory::new()->create());

        // When
        $result = $this->ask(new GetCustomerByIdentityId('identity-1'));

        // Then
        self::assertNull($result);
    }
}
