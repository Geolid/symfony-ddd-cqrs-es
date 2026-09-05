<?php

declare(strict_types=1);

namespace AfterSales\Tests\Return\Infrastructure\Command\RequestWithdrawal;

use AfterSales\Return\Application\Command\RequestWithdrawal\Exception\WithdrawalRequestInProgressException;
use AfterSales\Return\Application\Command\RequestWithdrawal\RequestWithdrawal;
use PHPUnit\Framework\Attributes\Test;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Lock\LockFactory;

final class LockingRequestWithdrawalHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itFailsWhenRequestInProgress(): void
    {
        // Given
        $builder = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered();
        $order = $builder->create();
        $this->store($order);
        $lock = $this->service(LockFactory::class)->createLock(\sprintf('aftersales.return.withdrawal_request.%s', $order->id->toString()), 5.0);
        $lock->acquire();

        // Then
        $this->expectException(WithdrawalRequestInProgressException::class);

        // When
        $this->dispatch(new RequestWithdrawal($order->id->toString(), $builder['buyerId']));
    }
}
