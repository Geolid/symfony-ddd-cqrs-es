<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Pii;

use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class OrderPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnOrderErasure(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // When
        $this->cipherKeyStore->removeWithSubjectId($order->id->toString());
        $erased = $this->storedEventOf(
            OrderConfirmed::class,
            static fn (OrderConfirmed $event): bool => $event->id->equals($order->id),
        );

        // Then
        $erasedPostalAddress = PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
        self::assertSame($erasedPostalAddress, PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsOrderConfirmedShippingAddressOnOrderErasure(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // When
        $this->cipherKeyStore->removeWithSubjectId($order->id->toString());
        $erased = $this->storedEventOf(
            OrderConfirmedIntegrationEvent::class,
            static fn (OrderConfirmedIntegrationEvent $event): bool => $event->orderId === $order->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), $erased->shippingAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return ['recipientName' => 'erased', 'address' => ['street' => 'erased', 'postalCode' => '00000', 'city' => 'erased', 'countryCode' => 'ZZ']];
    }
}
