<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\RejectWithdrawal;

use AfterSales\Return\Application\Command\RejectWithdrawal\RejectWithdrawal;
use AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected\WithdrawalRejectedIntegrationEvent;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
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
