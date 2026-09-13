<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ExpireCheckoutSession;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\ValueObject\CheckoutSessionId;

#[CommandHandler]
final readonly class ExpireCheckoutSessionHandler
{
    public function __construct(
        private CheckoutSessionRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CheckoutSessionNotFoundException
     * @throws CheckoutSessionAlreadyExistsException
     */
    public function __invoke(ExpireCheckoutSession $command): void
    {
        $checkoutSession = $this->repository->load(CheckoutSessionId::fromString($command->id));
        $checkoutSession->expire($this->clock->now());
        $this->repository->save($checkoutSession);
    }
}
