<?php

declare(strict_types=1);

namespace AfterSales\Tests\Withdrawal\Application\Command\ApproveWithdrawal;

use AfterSales\Tests\Withdrawal\Support\Builder\WithdrawalBuilder;
use AfterSales\Withdrawal\Application\Command\ApproveWithdrawal\ApproveWithdrawal;
use AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotReceivedException;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApproveWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ApproveWithdrawal($builder['orderId']));

        // Then
        $event = $this->publishedEventOf(WithdrawalApprovedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received()->approved();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ApproveWithdrawal($builder['orderId']));

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
        $this->dispatch(new ApproveWithdrawal($builder['orderId']));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->dispatch(new ApproveWithdrawal($orderId));
    }
}
