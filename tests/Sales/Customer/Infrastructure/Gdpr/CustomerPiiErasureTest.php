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
use Sales\Tests\Customer\Support\Builder\CustomerBuilder;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Gdpr\DataSubjectEraserProcessor;
use Support\AbstractIntegrationTestCase;

final class CustomerPiiErasureTest extends AbstractIntegrationTestCase
{
    private \DateTimeImmutable $erasedAt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->erasedAt = new \DateTimeImmutable('2026-01-02T00:00:00+00:00');
    }

    #[Test]
    public function itCryptoShredsEmailOnErasure(): void
    {
        // Given
        $customer = CustomerBuilder::new()->withEmail('buyer@example.com')->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerRegistered::class,
            static fn (CustomerRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraserProcessor::class))(
            Message::create(new CustomerErased($customer->id->toString(), $this->erasedAt)),
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
        $customer = CustomerBuilder::new()
            ->shippingAddressRegistered(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR')))
            ->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerShippingAddressRegistered::class,
            static fn (CustomerShippingAddressRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraserProcessor::class))(
            Message::create(new CustomerErased($customer->id->toString(), $this->erasedAt)),
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
        $customer = CustomerBuilder::new()
            ->billingAddressRegistered(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris', 'FR')))
            ->create();
        $this->store($customer);
        $serialized = $this->serializedEventOf(
            CustomerBillingAddressRegistered::class,
            static fn (CustomerBillingAddressRegistered $event): bool => $event->id === $customer->id->toString(),
        );

        // When
        ($this->service(DataSubjectEraserProcessor::class))(
            Message::create(new CustomerErased($customer->id->toString(), $this->erasedAt)),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerBillingAddressRegistered::class, $rehydrated);
        self::assertSame($this->erasedAddress(), $rehydrated->address);
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string, countryCode: string}
     */
    private function erasedAddress(): array
    {
        return ['firstName' => 'erased', 'lastName' => 'erased', 'street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ'];
    }
}
