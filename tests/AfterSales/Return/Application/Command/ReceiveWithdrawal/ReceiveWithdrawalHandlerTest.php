<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Application\Command\ReceiveWithdrawal;

use AfterSales\Return\Application\Command\ReceiveWithdrawal\ReceiveWithdrawal;
use AfterSales\Return\Domain\Event\WithdrawalReceived;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Tests\Return\Support\Builder\WithdrawalBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ReceiveWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReceives(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ReceiveWithdrawal($withdrawal->id->toString()));

        // Then
        $event = $this->publishedEventOf(WithdrawalReceived::class);
        self::assertSame($withdrawal->id->toString(), $event->id);
    }

    #[Test]
    public function itIgnoresWhenAlreadyReceived(): void
    {
        // Given
        $withdrawal = WithdrawalBuilder::new()->received()->create();
        $this->store($withdrawal);

        // When
        $this->dispatch(new ReceiveWithdrawal($withdrawal->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = WithdrawalId::generate()->toString();

        // Then
        $this->expectException(WithdrawalNotFoundException::class);

        // When
        $this->dispatch(new ReceiveWithdrawal($id));
    }
}
