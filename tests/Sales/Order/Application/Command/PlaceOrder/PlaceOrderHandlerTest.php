<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Command\PlaceOrder;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Domain\OrderId;
use Support\AbstractIntegrationTestCase;

final class PlaceOrderHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        // Given
        $id = OrderId::generate()->toString();
        $command = new PlaceOrder($id, 'customer-1', 1_999);

        // When
        $this->dispatch($command);

        // Then
        $results = array_values(iterator_to_array($this->service(OrderFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame('customer-1', $results[0]->customerId);
        self::assertSame(1_999, $results[0]->totalAmountInCents);
        self::assertSame('placed', $results[0]->status);
    }
}
