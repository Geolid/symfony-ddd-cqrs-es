<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Application\Command\RejectWithdrawal;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\Command\RejectWithdrawal\RejectWithdrawal;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotReceivedException;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RejectWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRejects(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $withdrawal = $builder->create();
        $this->store($withdrawal);
        $reason = WithdrawalBuilder::sample('reason');

        // When
        $this->dispatch(new RejectWithdrawal($builder['orderId'], $reason));

        // Then
        $event = $this->publishedEventOf(WithdrawalRejectedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($reason, $event->reason);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRejected(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received()->rejected();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new RejectWithdrawal($builder['orderId'], WithdrawalBuilder::sample('reason')));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotReceived(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // Then
        $this->expectException(WithdrawalNotReceivedException::class);

        // When
        $this->dispatch(new RejectWithdrawal($builder['orderId'], WithdrawalBuilder::sample('reason')));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->dispatch(new RejectWithdrawal($orderId, WithdrawalBuilder::sample('reason')));
    }
}
