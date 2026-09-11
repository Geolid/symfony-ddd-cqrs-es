<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Finder;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Sales\Ordering\Application\Finder\CheckoutSession\Exception\CheckoutSessionResultNotFoundException;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class DbalCheckoutSessionFinderTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();
        $this->store($other, $checkoutSession);

        // When
        $result = $this->finder->ofId($checkoutSession->id->toString());

        // Then
        self::assertSame($checkoutSession->id->toString(), $result->checkoutSessionId);
        self::assertSame($builder['cartId'], $result->cartId);
        self::assertSame($builder['shopperId'], $result->shopperId);
        self::assertSame(PostalAddressMapper::toArray($builder['shippingAddress']), [
            'recipientName' => $result->shippingAddress->recipientName,
            'address' => (array) $result->shippingAddress->address,
        ]);
        self::assertSame(PostalAddressMapper::toArray($builder['billingAddress']), [
            'recipientName' => $result->billingAddress->recipientName,
            'address' => (array) $result->billingAddress->address,
        ]);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(CheckoutSessionResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }
}
