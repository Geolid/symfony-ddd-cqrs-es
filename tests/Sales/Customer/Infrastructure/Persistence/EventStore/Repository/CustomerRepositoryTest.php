<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Repository;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class CustomerRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedCustomer(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->create();

        // When
        $this->repository->save($customer);

        // Then
        $id = $customer->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('buyer@example.com', $this->repository->load($id)->email()->toString());
    }

    #[Test]
    public function itLoadsARegisteredCustomerWithNoAddressYet(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();

        // When
        $reloaded = $this->repository->load($customer->id());

        // Then
        self::assertNull($reloaded->shippingAddress());
        self::assertNull($reloaded->billingAddress());
    }

    #[Test]
    public function itSavesAndReloadsBothAddresses(): void
    {
        // Given
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));
        $customer = CustomerTestFactory::new()
            ->withShippingAddress($shippingAddress)
            ->withBillingAddress($billingAddress)
            ->create();

        // When
        $this->repository->save($customer);

        // Then
        $reloaded = $this->repository->load($customer->id());
        self::assertTrue(null !== $reloaded->shippingAddress() && $shippingAddress->equals($reloaded->shippingAddress()));
        self::assertTrue(null !== $reloaded->billingAddress() && $billingAddress->equals($reloaded->billingAddress()));
    }

    #[Test]
    public function itThrowsOnAnUnsavedCustomer(): void
    {
        // Given
        $id = CustomerId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(CustomerNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
