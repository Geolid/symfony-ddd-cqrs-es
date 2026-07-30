<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Application\Command\RegisterCustomer;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Application\Command\RegisterCustomer\RegisterCustomer;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Domain\CustomerId;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
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
        $results = array_values(iterator_to_array($this->service(CustomerFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame('buyer@example.com', $results[0]->email);
        self::assertNull($results[0]->erasedAt);
    }

    #[Test]
    public function itFailsWhenTheAddressIsAlreadyRegistered(): void
    {
        // Given
        $this->dispatch(new RegisterCustomer(CustomerId::generate()->toString(), 'buyer@example.com'));

        // Then
        $this->expectException(UniqueValueAlreadyTakenException::class);

        // When
        $this->dispatch(new RegisterCustomer(CustomerId::generate()->toString(), 'BUYER@example.com'));
    }
}
