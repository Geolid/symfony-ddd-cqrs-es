<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Symfony\Component\Console\Command\Command;

final class PlaceOrderCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        // When
        $tester = $this->tester('sales:order:place');
        $tester->execute(['customer-id' => 'customer-1', 'total-amount-in-cents' => '4200']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertCount(1, iterator_to_array($this->service(OrderFinderInterface::class)));
    }
}
