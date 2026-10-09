<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Pii;

use Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined\CustomerBillingAddressDefinedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerShippingAddressDefined\CustomerShippingAddressDefinedIntegrationEvent;
use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());
        $erased = $this->storedEventOf(
            CustomerShippingAddressDefined::class,
            static fn (CustomerShippingAddressDefined $event): bool => $event->id->equals($customer->id),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->postalAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()
            ->billingAddressDefined()
            ->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());
        $erased = $this->storedEventOf(
            CustomerBillingAddressDefined::class,
            static fn (CustomerBillingAddressDefined $event): bool => $event->id->equals($customer->id),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->postalAddress));
    }

    #[Test]
    public function itCryptoShredsCustomerShippingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()->shippingAddressDefined()->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());
        $erased = $this->storedEventOf(
            CustomerShippingAddressDefinedIntegrationEvent::class,
            static fn (CustomerShippingAddressDefinedIntegrationEvent $event): bool => $event->customerId === $customer->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), $erased->postalAddress);
    }

    #[Test]
    public function itCryptoShredsCustomerBillingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerFactory::new()->billingAddressDefined()->create();
        $this->store($customer);

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());
        $erased = $this->storedEventOf(
            CustomerBillingAddressDefinedIntegrationEvent::class,
            static fn (CustomerBillingAddressDefinedIntegrationEvent $event): bool => $event->customerId === $customer->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), $erased->postalAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
