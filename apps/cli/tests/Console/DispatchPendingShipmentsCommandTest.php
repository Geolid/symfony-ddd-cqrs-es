<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class DispatchPendingShipmentsCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itDisplaysNoPendingShipments(): void
    {
        // When
        $tester = $this->tester('fulfilment:fulfilment:shipment:dispatch-pending');
        $tester->execute([]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('0 shipment(s) dispatched.', $tester->getDisplay());
    }
}
