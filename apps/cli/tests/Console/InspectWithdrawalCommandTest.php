<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use Cli\Tests\Support\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class InspectWithdrawalCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itApprovesAWithdrawal(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $withdrawal = $builder->create();
        $this->store($withdrawal);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'after-sales:withdrawal:inspect', 'order-id' => $builder['orderId'], '--approve' => true]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('approved', $tester->getDisplay());
        $event = $this->publishedEventOf(WithdrawalApprovedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
    }

    #[Test]
    public function itRejectsAWithdrawal(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $withdrawal = $builder->create();
        $this->store($withdrawal);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'after-sales:withdrawal:inspect', 'order-id' => $builder['orderId'], '--reject' => 'Item shows signs of use.']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('rejected', $tester->getDisplay());
        $event = $this->publishedEventOf(WithdrawalRejectedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame('Item shows signs of use.', $event->reason);
    }

    #[Test]
    public function itFailsToInspectWithoutExactlyOneOutcome(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $this->store($builder->create());
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'after-sales:withdrawal:inspect', 'order-id' => $builder['orderId']]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Specify exactly one of --approve or --reject', $tester->getDisplay());
    }
}
