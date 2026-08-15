<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\RegisterCustomer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\CustomerEmailAlreadyRegisteredException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Customer\Domain\ValueObject\CustomerUniqueValue;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Email;
use Support\AbstractIntegrationTestCase;

final class RegisterCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRegistersACustomer(): void
    {
        // Given
        $id = CustomerId::generate()->toString();
        $command = new RegisterCustomer($id, 'Buyer@Example.COM');

        // When
        $this->dispatch($command);

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame('buyer@example.com', $result->email);
    }

    #[Test]
    public function itFailsWhenTheAddressIsAlreadyRegistered(): void
    {
        // Given
        $this->service(UniqueValueRegistryInterface::class)->reserve(CustomerUniqueValue::EMAIL, Email::fromString('buyer@example.com')->fingerprint());

        // Then
        $this->expectException(CustomerEmailAlreadyRegisteredException::class);

        // When
        $this->dispatch(new RegisterCustomer(CustomerId::generate()->toString(), 'BUYER@example.com'));
    }
}
