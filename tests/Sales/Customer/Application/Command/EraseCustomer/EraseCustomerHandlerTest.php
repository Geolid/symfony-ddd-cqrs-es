<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\EraseCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Support\AbstractIntegrationTestCase;

final class EraseCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->dispatch(new EraseCustomer($customer->id()->toString()));
        $this->service(CustomerFinderInterface::class)->ofId($customer->id()->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->erased()->store();

        // When
        $this->dispatch(new EraseCustomer($customer->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();

        // Then
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->dispatch(new EraseCustomer($id));
    }
}
