<?php

declare(strict_types=1);

namespace AfterSales\Return\Infrastructure\Command\RequestWithdrawal;

use AfterSales\Return\Application\Command\RequestWithdrawal\RequestWithdrawal;
use AfterSales\Return\Application\Command\RequestWithdrawal\RequestWithdrawalHandler;
use AfterSales\Return\Application\Exception\WithdrawalRequestInProgressException;
use Shared\Infrastructure\Locking\LockingTrait;
use Shared\Infrastructure\Locking\LockNotAcquiredException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Lock\LockFactory;

#[AsDecorator(decorates: RequestWithdrawalHandler::class)]
final readonly class LockingRequestWithdrawalHandler
{
    use LockingTrait;

    // Pure in-process check-then-create, no external I/O — a short TTL already
    // covers the slowest realistic Finder query plus Event Store save under contention.
    private const float LOCK_TTL_SECONDS = 5.0;

    public function __construct(
        #[AutowireDecorated]
        private RequestWithdrawalHandler $inner,
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * @throws WithdrawalRequestInProgressException
     */
    public function __invoke(RequestWithdrawal $command): void
    {
        try {
            $this->withLock(
                \sprintf('aftersales.return.withdrawal_request.%s', $command->orderId),
                self::LOCK_TTL_SECONDS,
                function () use ($command): void {
                    ($this->inner)($command);
                },
            );
        } catch (LockNotAcquiredException $e) {
            throw WithdrawalRequestInProgressException::forOrder($command->orderId, $e);
        }
    }

    private function locks(): LockFactory
    {
        return $this->lockFactory;
    }
}
