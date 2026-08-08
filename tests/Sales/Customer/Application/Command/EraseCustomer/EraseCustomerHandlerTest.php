<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\EraseCustomer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Support\AbstractIntegrationTestCase;

final class EraseCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRedactsTheAddress(): void
    {
        // Given
        $id = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($id, 'buyer@example.com'));

        // When
        $this->dispatch(new EraseCustomer($id));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertNull($result->email);
        self::assertNotNull($result->erasedAt);
    }

    #[Test]
    public function itFreesTheAddressForAnotherCustomer(): void
    {
        // Given
        $erased = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($erased, 'buyer@example.com'));
        $this->dispatch(new EraseCustomer($erased));

        // When
        $this->dispatch(new RegisterCustomer($id = CustomerId::generate()->toString(), 'buyer@example.com'));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertSame('buyer@example.com', $result->email);
    }

    #[Test]
    public function itIgnoresASecondErasure(): void
    {
        // Given
        $id = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($id, 'buyer@example.com'));
        $this->dispatch(new EraseCustomer($id));

        // When
        $this->dispatch(new EraseCustomer($id));

        // Then
        $result = $this->service(CustomerFinderInterface::class)->ofId($id);
        self::assertNull($result->email);
    }

    #[Test]
    public function itRefusesAnAddressAlreadyTakenAfterASecondErasure(): void
    {
        // Given
        $erased = CustomerId::generate()->toString();
        $this->dispatch(new RegisterCustomer($erased, 'buyer@example.com'));
        $this->dispatch(new EraseCustomer($erased));
        $this->dispatch(new RegisterCustomer(CustomerId::generate()->toString(), 'buyer@example.com'));
        $this->dispatch(new EraseCustomer($erased));

        // Then
        $this->expectException(AddressAlreadyRegisteredException::class);

        // When
        $this->dispatch(new RegisterCustomer(CustomerId::generate()->toString(), 'buyer@example.com'));
    }
}
