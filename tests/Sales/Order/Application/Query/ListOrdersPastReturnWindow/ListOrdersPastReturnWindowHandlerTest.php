<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Query\ListOrdersPastReturnWindow;

use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Query\ListOrdersPastReturnWindow\ListOrdersPastReturnWindow;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ListOrdersPastReturnWindowHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itLists(): void
    {
        // Given
        $now = Clock::get()->now();
        $expired = OrderBuilder::new()->confirmed()->dispatched()
            ->delivered($now->modify('-19 days'))
            ->create();
        $withinWindow = OrderBuilder::new()->confirmed()->dispatched()
            ->delivered($now->modify('-2 days'))
            ->create();
        $this->store($withinWindow, $expired);

        // When
        $results = iterator_to_array($this->ask(new ListOrdersPastReturnWindow()), false);

        // Then
        self::assertCount(1, $results);
        self::assertSame($expired->id->toString(), $results[0]->id);
    }
}
