<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\EventStore;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class PatchlevelOrderRepositoryTest extends AbstractIntegrationTestCase
{
    private OrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(OrderRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsSaved(): void
    {
        // Given
        $order = OrderTestFactory::new()->create();

        // When
        $this->repository->save($order);

        // Then
        $id = $order->id;
        self::assertTrue($this->repository->has($id));
        $shippingAddress = $order->shippingAddress;
        $reloadedShippingAddress = $this->repository->load($id)->shippingAddress;
        self::assertSame(
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
            [
                'firstName' => $reloadedShippingAddress->fullName->firstName,
                'lastName' => $reloadedShippingAddress->fullName->lastName,
                'street' => $reloadedShippingAddress->address->street,
                'postalCode' => $reloadedShippingAddress->address->postalCode,
                'city' => $reloadedShippingAddress->address->city,
                'countryCode' => $reloadedShippingAddress->address->countryCode->value,
            ],
        );
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = OrderId::generate();

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
