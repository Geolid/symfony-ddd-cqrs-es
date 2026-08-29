<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Customer\Domain\Repository\CustomerRepositoryInterface;
use Sales\Customer\Domain\ValueObject\CustomerId;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class PatchlevelCustomerRepositoryTest extends AbstractIntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CustomerRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsSaved(): void
    {
        // Given
        $shippingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR'));
        $billingAddress = PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR'));
        $customer = CustomerTestFactory::new()
            ->withEmail('buyer@example.com')
            ->withShippingAddress($shippingAddress)
            ->withBillingAddress($billingAddress)
            ->create();

        // When
        $this->repository->save($customer);

        // Then
        $id = $customer->id;
        self::assertTrue($this->repository->has($id));
        $reloaded = $this->repository->load($id);
        self::assertSame('buyer@example.com', $reloaded->email->value);
        self::assertNotNull($reloaded->shippingAddress);
        self::assertSame(
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
            [
                'firstName' => $reloaded->shippingAddress->fullName->firstName,
                'lastName' => $reloaded->shippingAddress->fullName->lastName,
                'street' => $reloaded->shippingAddress->address->street,
                'postalCode' => $reloaded->shippingAddress->address->postalCode,
                'city' => $reloaded->shippingAddress->address->city,
                'countryCode' => $reloaded->shippingAddress->address->countryCode->value,
            ],
        );
        self::assertNotNull($reloaded->billingAddress);
        self::assertSame(
            [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
                'countryCode' => $billingAddress->address->countryCode->value,
            ],
            [
                'firstName' => $reloaded->billingAddress->fullName->firstName,
                'lastName' => $reloaded->billingAddress->fullName->lastName,
                'street' => $reloaded->billingAddress->address->street,
                'postalCode' => $reloaded->billingAddress->address->postalCode,
                'city' => $reloaded->billingAddress->address->city,
                'countryCode' => $reloaded->billingAddress->address->countryCode->value,
            ],
        );
    }

    #[Test]
    public function itThrowsOnUnsaved(): void
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
