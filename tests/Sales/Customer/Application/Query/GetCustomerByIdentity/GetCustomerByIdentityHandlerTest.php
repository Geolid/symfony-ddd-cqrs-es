<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Query\GetCustomerByIdentity;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Query\GetCustomerByIdentity\GetCustomerByIdentity;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class GetCustomerByIdentityHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsACustomerByIdentity(): void
    {
        // Given
        $identityId = Uuid::uuid7()->toString();
        $customer = CustomerTestFactory::new()->linkedToIdentity($identityId)->create();
        $this->store($customer);
        $this->store(CustomerTestFactory::new()->create());

        // When
        $result = $this->ask(new GetCustomerByIdentity($identityId));

        // Then
        self::assertSame($customer->id()->toString(), $result->id);
    }

    #[Test]
    public function itFailsWhenNoCustomerIsLinkedToTheIdentity(): void
    {
        // Given
        $this->store(CustomerTestFactory::new()->create());

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->ask(new GetCustomerByIdentity(Uuid::uuid7()->toString()));
    }
}
