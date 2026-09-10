<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\DefineShopperShippingAddress;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[CommandHandler]
final readonly class DefineShopperShippingAddressHandler
{
    public function __construct(
        private ShopperRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperNotFoundException
     * @throws ShopperAlreadyExistsException
     */
    public function __invoke(DefineShopperShippingAddress $command): void
    {
        $shopper = $this->repository->load(ShopperId::fromString($command->shopperId));

        $shopper->defineShippingAddress(
            PostalAddress::of($command->shippingAddress['recipientName'], Address::of(...$command->shippingAddress['address'])),
            $this->clock->now(),
        );

        $this->repository->save($shopper);
    }
}
