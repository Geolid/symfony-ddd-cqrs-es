<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\EraseCustomer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\EraseCustomer\EraseCustomer;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Application\Finder\Customer\CustomerResult;
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
        $results = $this->allCustomers();
        self::assertCount(1, $results);
        self::assertNull($results[0]->email);
        self::assertNotNull($results[0]->erasedAt);
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
        $emails = [];

        foreach ($this->service(CustomerFinderInterface::class) as $customer) {
            $emails[$customer->id] = $customer->email;
        }

        self::assertSame('buyer@example.com', $emails[$id]);
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
        $results = $this->allCustomers();
        self::assertCount(1, $results);
        self::assertNull($results[0]->email);
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

    /**
     * @return list<CustomerResult>
     */
    private function allCustomers(): array
    {
        return array_values(iterator_to_array($this->service(CustomerFinderInterface::class)));
    }
}
