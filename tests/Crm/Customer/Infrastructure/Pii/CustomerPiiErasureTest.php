<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Pii;

use Crm\Customer\Application\IntegrationEvent\CustomerBillingAddressDefined\CustomerBillingAddressDefinedIntegrationEvent;
use Crm\Customer\Application\IntegrationEvent\CustomerShippingAddressDefined\CustomerShippingAddressDefinedIntegrationEvent;
use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
        $this->serializer = $this->service(EventSerializer::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerShippingAddressDefined::class,
            static fn (CustomerShippingAddressDefined $event): bool => $event->id->equals($customer->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CustomerShippingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->postalAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()
            ->billingAddressDefined()
            ->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerBillingAddressDefined::class,
            static fn (CustomerBillingAddressDefined $event): bool => $event->id->equals($customer->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CustomerBillingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->postalAddress));
    }

    #[Test]
    public function itCryptoShredsCustomerShippingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()->shippingAddressDefined()->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerShippingAddressDefinedIntegrationEvent::class,
            static fn (CustomerShippingAddressDefinedIntegrationEvent $event): bool => $event->customerId === $customer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CustomerShippingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->postalAddress);
    }

    #[Test]
    public function itCryptoShredsCustomerBillingAddressDefinedIntegrationEventDataOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()->billingAddressDefined()->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerBillingAddressDefinedIntegrationEvent::class,
            static fn (CustomerBillingAddressDefinedIntegrationEvent $event): bool => $event->customerId === $customer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CustomerBillingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->postalAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
