<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Event\CustomerBillingAddressRegistered;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Event\CustomerShippingAddressRegistered;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Gdpr\DataSubjectEraser;
use Support\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsEmailOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();
        $serialized = $this->serializedEventOf(
            CustomerRegistered::class,
            static fn (CustomerRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new CustomerErased($customer->id->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerRegistered::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('%s@erased.invalid');
        self::assertSame($sentinel($customer->id->toString()), $rehydrated->email);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->store();
        $serialized = $this->serializedEventOf(
            CustomerShippingAddressRegistered::class,
            static fn (CustomerShippingAddressRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new CustomerErased($customer->id->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerShippingAddressRegistered::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->address);
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()
            ->withBillingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')))
            ->store();
        $serialized = $this->serializedEventOf(
            CustomerBillingAddressRegistered::class,
            static fn (CustomerBillingAddressRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraser::class))(
            Message::create(new CustomerErased($customer->id->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerBillingAddressRegistered::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->address);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function erasedAddress(): array
    {
        return ['firstName' => 'erased', 'lastName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased'];
    }
}
