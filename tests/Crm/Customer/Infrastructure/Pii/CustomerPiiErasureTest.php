<?php

declare(strict_types=1);

namespace Crm\Tests\Customer\Infrastructure\Pii;

use Crm\Customer\Domain\Customer\Event\CustomerBillingAddressDefined;
use Crm\Customer\Domain\Customer\Event\CustomerRegistered;
use Crm\Customer\Domain\Customer\Event\CustomerShippingAddressDefined;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedFieldSentinel;
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
    public function itCryptoShredsRegisteredDataOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerRegistered::class,
            static fn (CustomerRegistered $event): bool => $event->id->equals($customer->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($customer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CustomerRegistered::class, $rehydrated);
        self::assertSame('Erased', $rehydrated->firstName->value);
        self::assertSame('Erased', $rehydrated->lastName->value);
        $sentinel = new ErasedFieldSentinel('%s@erased.invalid');
        $expectedEmail = $sentinel($customer->id->toString());
        self::assertSame($expectedEmail, $rehydrated->email->value);
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

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
