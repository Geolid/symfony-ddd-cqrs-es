<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\Command\RequestWithdrawal;

use AfterSales\Withdrawal\Application\Exception\OrderResultNotFoundException;
use AfterSales\Withdrawal\Application\Finder\Order\OrderFinderInterface;
use AfterSales\Withdrawal\Domain\Exception\CannotRequestWithdrawalForAnotherCustomerException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalWindowExpiredException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class RequestWithdrawalHandler
{
    public function __construct(
        private WithdrawalRepositoryInterface $repository,
        private OrderFinderInterface $orderFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws OrderResultNotFoundException
     * @throws CannotRequestWithdrawalForAnotherCustomerException
     * @throws WithdrawalWindowExpiredException
     * @throws WithdrawalAlreadyExistsException
     */
    public function __invoke(RequestWithdrawal $command): void
    {
        $id = WithdrawalId::forOrder($command->orderId);

        if ($this->repository->has($id)) {
            return;
        }

        $order = $this->orderFinder->ofId($command->orderId);

        $withdrawal = Withdrawal::request(
            id: $id,
            orderId: $order->orderId,
            customerId: $order->customerId,
            actingCustomerId: $command->customerId,
            shippingAddress: PostalAddress::of(
                $order->shippingAddress->recipientName,
                Address::of($order->shippingAddress->street, $order->shippingAddress->postalCode, $order->shippingAddress->city, $order->shippingAddress->countryCode),
            ),
            deliveredAt: $order->deliveredAt,
            now: $this->clock->now(),
        );

        try {
            $this->repository->save($withdrawal);
        } catch (WithdrawalAlreadyExistsException) {
            return;
        }
    }
}
