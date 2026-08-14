<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Persistence\EventStore\Repository;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class CustomerAddressesRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerAddressesRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerAddressesRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsARegisteredCustomerNotYetSet(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();

        // When
        $customerAddresses = $this->repository->load($customer->id());

        // Then
        self::assertTrue($this->repository->has($customer->id()));
        self::assertNull($customerAddresses->shippingAddress());
        self::assertNull($customerAddresses->billingAddress());
    }

    #[Test]
    public function itSetsBothAddressesAndReloadsFromTheSharedStream(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris'));
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris'));
        $customerAddresses = $this->repository->load($customer->id());
        $customerAddresses->setShippingAddress($shippingAddress, new \DateTimeImmutable('now +00:00'));
        $customerAddresses->setBillingAddress($billingAddress, new \DateTimeImmutable('now +00:00'));

        // When
        $this->repository->save($customerAddresses);

        // Then
        $reloaded = $this->repository->load($customer->id());
        self::assertTrue(null !== $reloaded->shippingAddress() && $shippingAddress->equals($reloaded->shippingAddress()));
        self::assertTrue(null !== $reloaded->billingAddress() && $billingAddress->equals($reloaded->billingAddress()));
    }

    #[Test]
    public function itThrowsOnAnUnregisteredCustomer(): void
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
