<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\Command\RequestWithdrawal;

use AfterSales\Return\Application\Command\RequestWithdrawal\Exception\ActiveWithdrawalAlreadyExistsException;
use AfterSales\Return\Application\Finder\DeliveredOrder\DeliveredOrderFinderInterface;
use AfterSales\Return\Application\Finder\DeliveredOrder\Exception\DeliveredOrderResultNotFoundException;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
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
        private WithdrawalFinderInterface $withdrawalFinder,
        private DeliveredOrderFinderInterface $orderFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws DeliveredOrderResultNotFoundException
     * @throws CannotRequestWithdrawalForAnotherBuyerException
     * @throws WithdrawalWindowExpiredException
     * @throws WithdrawalAlreadyExistsException
     * @throws ActiveWithdrawalAlreadyExistsException
     */
    public function __invoke(RequestWithdrawal $command): void
    {
        if ($this->withdrawalFinder->byOrder($command->orderId)->active()->count() > 0) {
            throw ActiveWithdrawalAlreadyExistsException::forOrder($command->orderId);
        }

        $order = $this->orderFinder->ofId($command->orderId);

        $withdrawal = Withdrawal::request(
            id: WithdrawalId::generate(),
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
