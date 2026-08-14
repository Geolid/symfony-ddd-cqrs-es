<?php

declare(strict_types=1);

namespace Sales\Tests\Customer\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use PHPUnit\Framework\Attributes\Test;
use Sales\Customer\Domain\Event\CustomerBillingAddressSet;
use Sales\Customer\Domain\Event\CustomerErased;
use Sales\Customer\Domain\Event\CustomerRegistered;
use Sales\Customer\Domain\Repository\CustomerAddressesRepositoryInterface;
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
    public function itCryptoShredsTheEmailOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->withEmail('buyer@example.com')->store();
        $serialized = $this->serializedEventOf(
            CustomerRegistered::class,
            static fn (CustomerRegistered $event): bool => $event->id === $customer->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new CustomerErased($customer->id()->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerRegistered::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('erased-email-%s@customer.invalid');
        self::assertSame($sentinel($customer->id()->toString()), $rehydrated->email);
    }

    #[Test]
    public function itCryptoShredsTheBillingAddressOnErasure(): void
    {
        // Given
        $customer = CustomerTestFactory::new()->store();
        $customerAddresses = $this->service(CustomerAddressesRepositoryInterface::class)->load($customer->id());
        $customerAddresses->setBillingAddress(
            PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('8 avenue Foch', '75116', 'Paris')),
            new \DateTimeImmutable('now +00:00'),
        );
        $this->store($customerAddresses);
        $serialized = $this->serializedEventOf(
            CustomerBillingAddressSet::class,
            static fn (CustomerBillingAddressSet $event): bool => $event->id === $customer->id()->toString(),
        );

        // When
        $this->service(DataSubjectEraser::class)->onEvent(
            Message::create(new CustomerErased($customer->id()->toString(), '2026-01-02T00:00:00+00:00')),
        );

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(CustomerBillingAddressSet::class, $rehydrated);
        self::assertSame('Erased', $rehydrated->street);
        self::assertSame('00000', $rehydrated->postalCode);
    }
}
