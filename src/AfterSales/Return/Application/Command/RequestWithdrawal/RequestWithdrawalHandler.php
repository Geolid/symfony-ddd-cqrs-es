<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RequestWithdrawal;

use AfterSales\Return\Application\Exception\OrderResultNotFoundException;
use AfterSales\Return\Application\Finder\Order\OrderFinderInterface;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherBuyerException;
use AfterSales\Return\Domain\Exception\WithdrawalAlreadyExistsException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;
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
     * @throws CannotRequestWithdrawalForAnotherBuyerException
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
            buyerId: $order->buyerId,
            actingBuyerId: $command->buyerId,
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
