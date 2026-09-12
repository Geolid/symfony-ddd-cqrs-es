<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Pii;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsAddressesOnOrderErasure(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderConfirmed::class,
            static fn (OrderConfirmed $event): bool => $event->id->equals($order->id),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($order->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(OrderConfirmed::class, $rehydrated);
        $erasedPostalAddress = PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
        self::assertSame($erasedPostalAddress, PostalAddressMapper::toArray($rehydrated->shippingAddress));
        self::assertSame($erasedPostalAddress, PostalAddressMapper::toArray($rehydrated->billingAddress));
    }

    #[Test]
    public function itCryptoShredsOrderConfirmedShippingAddressOnOrderErasure(): void
    {
        // Given
        $order = OrderBuilder::new()->create();
        $this->store($order);
        $serialized = $this->serializedEventOf(
            OrderConfirmedIntegrationEvent::class,
            static fn (OrderConfirmedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($order->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(OrderConfirmedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->shippingAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return ['recipientName' => 'erased', 'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ']];
    }
}
