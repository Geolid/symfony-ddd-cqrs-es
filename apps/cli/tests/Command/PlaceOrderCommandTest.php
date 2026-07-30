<?php

declare(strict_types=1);

namespace Cli\Tests\Command;

use Cli\Tests\Support\AbstractCliTestCase;
use Ordering\Order\Application\Finder\Order\OrderFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class PlaceOrderCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itPlacesAnOrder(): void
    {
        // When
        $tester = $this->tester('order:place');
        $tester->execute(['customer-id' => 'customer-1', 'total-amount-in-cents' => '4200']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertCount(1, iterator_to_array($this->service(OrderFinderInterface::class)));
    }
}
