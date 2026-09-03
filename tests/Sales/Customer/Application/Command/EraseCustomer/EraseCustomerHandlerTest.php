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
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class EraseCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErases(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->email->value, $customer->id->toString());

        // When
        $this->dispatch(new EraseCustomer($customer->id->toString()));

        // Then
        self::assertFalse($this->service(UniqueValueRegistryInterface::class)->exists(UniqueKey::for(CustomerUniqueKey::EMAIL), $customer->email->value));
        $this->expectException(CustomerResultNotFoundException::class);
        $this->service(CustomerFinderInterface::class)->ofId($customer->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyErased(): void
    {
        // Given
        $customer = CustomerBuilder::new()->erased()->create();
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
