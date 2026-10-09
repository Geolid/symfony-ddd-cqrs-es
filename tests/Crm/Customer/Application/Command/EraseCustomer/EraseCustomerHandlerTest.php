<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Application\Command\EraseCustomer;

use Crm\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Crm\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Crm\Customer\Domain\Customer\Exception\CustomerNotFoundException;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $customer = CustomerFactory::new()->erasureRequested()->create();
        $this->store($customer);

        // When
        $this->dispatch(new EraseCustomer($customer->id->toString()));

        // Then
        self::assertNull($this->service(CustomerFinderInterface::class)->ofIdOrNull($customer->id->toString()));
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $customer = CustomerFactory::new()->erasureRequested()->erased()->create();
        $this->store($customer);

        // When
        $this->dispatch(new EraseCustomer($customer->id->toString()));

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
