<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\ReceiveWithdrawal;

use AfterSales\Return\Application\Command\ReceiveWithdrawal\ReceiveWithdrawal;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class ReceiveWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReceives(): void
    {
        // Given
        $builder = WithdrawalBuilder::new();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ReceiveWithdrawal($builder['orderId']));

        // Then
        $event = $this->publishedEventOf(WithdrawalReceived::class);
        self::assertSame($withdrawal->id->toString(), $event->id);
    }

    #[Test]
    public function itIgnoresWhenAlreadyReceived(): void
    {
        // Given
        $builder = WithdrawalBuilder::new()->received();
        $withdrawal = $builder->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ReceiveWithdrawal($builder['orderId']));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->dispatch(new ReceiveWithdrawal($orderId));
    }
}
