<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\RegisterCustomer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\CustomerEmailAlreadyRegisteredException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueKey;
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class RegisterCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $id = CustomerId::generate()->toString();
        $email = CustomerBuilder::sample('email')->value;

        // When
        $this->dispatch(new RegisterCustomer($id, $email));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($email, $result->email);
    }

    #[Test]
    public function itFailsWhenEmailAlreadyRegistered(): void
    {
        // Given
        $id = CustomerId::generate()->toString();
        $existingId = CustomerId::generate()->toString();
        $email = CustomerBuilder::sample('email')->value;
        $this->service(UniqueValueRegistryInterface::class)->reserve(UniqueKey::for(CustomerUniqueKey::EMAIL), $email, $existingId);

        // Then
        $this->expectException(CustomerEmailAlreadyRegisteredException::class);

        // When
        $this->dispatch(new RegisterCustomer($id, $email));
    }
}
