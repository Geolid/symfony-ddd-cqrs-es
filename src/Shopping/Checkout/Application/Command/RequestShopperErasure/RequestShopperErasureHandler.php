<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RequestShopperErasure;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\ShopperNotFoundException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[CommandHandler]
final readonly class RequestShopperErasureHandler
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
    public function __invoke(RequestShopperErasure $command): void
    {
        $shopper = $this->repository->load(ShopperId::fromString($command->id));
        $shopper->requestErasure($this->clock->now());
        $this->repository->save($shopper);
    }
}
