<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\Command\AbandonCartCheckout;

use Psr\Clock\ClockInterface;
use Sales\Ordering\Domain\Cart\Exception\CartAlreadyExistsException;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Cart\ValueObject\CartId;
use Shared\Application\Command\CommandHandler;

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
