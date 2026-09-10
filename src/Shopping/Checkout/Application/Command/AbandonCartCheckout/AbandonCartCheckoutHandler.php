<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\AbandonCartCheckout;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Cart\Exception\CartAlreadyExistsException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\CartId;

#[CommandHandler]
final readonly class AbandonCartCheckoutHandler
{
    public function __construct(
        private CartRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CartNotFoundException
     * @throws CartAlreadyExistsException
     */
    public function __invoke(AbandonCartCheckout $command): void
    {
        $cart = $this->repository->load(CartId::fromString($command->id));
        $cart->abandonCheckout($this->clock->now());
        $this->repository->save($cart);
    }
}
