<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\ApproveWithdrawal;

use AfterSales\Return\Application\Command\ApproveWithdrawal\ApproveWithdrawal;
use AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved\WithdrawalApprovedIntegrationEvent;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
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
        $this->dispatch(new ApproveWithdrawal($withdrawal->id->toString()));

        // Then
        $event = $this->publishedEventOf(WithdrawalApprovedIntegrationEvent::class);
        self::assertSame($withdrawal->id->toString(), $event->withdrawalId);
        self::assertSame($builder['orderId'], $event->orderId);
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->received()->approved()->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ApproveWithdrawal($withdrawal->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotReceived(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->create();
        $this->store($withdrawal);

        // Then
        $this->expectException(WithdrawalNotReceivedException::class);

        // When
        $this->dispatch(new ApproveWithdrawal($withdrawal->id->toString()));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = WithdrawalId::generate()->toString();

        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->dispatch(new ApproveWithdrawal($id));
    }
}
