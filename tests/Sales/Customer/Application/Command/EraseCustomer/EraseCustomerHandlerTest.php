<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\EraseCustomer;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class EraseCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->create();
        $this->store($customer);
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->email->value, $customer->id->toString());

        // Then
        $this->expectException(CustomerResultNotFoundException::class);

        // When
        $this->dispatch(new EraseCustomer($customer->id->toString()));
        self::assertFalse($this->service(UniqueValueRegistryInterface::class)->exists(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->email->value));
        $this->service(CustomerFinderInterface::class)->ofId($customer->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->erased()->create();
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
