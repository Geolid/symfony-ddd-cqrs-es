<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\EraseOrderBillingAddress;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\EraseOrderBillingAddress\EraseOrderBillingAddress;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class EraseOrderBillingAddressHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itErasesTheBillingAddressOfAPlacedOrder(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();

        // Then
        $this->expectException(OrderResultNotFoundException::class);

        // When
        $this->dispatch(new EraseOrderBillingAddress($order->id()->toString()));
        $this->service(OrderFinderInterface::class)->ofId($order->id()->toString());
    }

    #[Test]
    public function itFailsWhenTheOrderDoesNotExist(): void
    {
        // Then
        $this->expectException(OrderNotFoundException::class);

        // When
        $this->dispatch(new EraseOrderBillingAddress(Uuid::uuid7()->toString()));
    }
}
