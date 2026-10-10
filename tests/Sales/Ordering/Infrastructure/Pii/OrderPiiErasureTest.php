<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Pii;

use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\IntegrationEvent\OrderConfirmed\OrderConfirmedIntegrationEvent;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Tests\Ordering\Support\Factory\OrderFactory;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;
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

        // Then
        $erased = $this->storedEventOf(OrderConfirmed::class, $order->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())($order->id->toString())), PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsOrderConfirmedShippingAddressOnOrderErasure(): void
    {
        // Given
        $order = OrderFactory::new()->create();
        $this->store($order);

        // When
        $this->cipherKeyStore->removeWithSubjectId($order->id->toString());

        // Then
        $erased = $this->storedEventOf(OrderConfirmedIntegrationEvent::class, $order->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())($order->id->toString())), $erased->shippingAddress);
    }
}
