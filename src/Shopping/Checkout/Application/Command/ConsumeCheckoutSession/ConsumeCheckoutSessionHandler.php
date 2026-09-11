<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\ConsumeCheckoutSession;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\CheckoutSession\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;

#[CommandHandler]
final readonly class ConsumeCheckoutSessionHandler
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
    public function __invoke(ConsumeCheckoutSession $command): void
    {
        $checkoutSession = $this->repository->load(CheckoutSessionId::fromString($command->id));
        $checkoutSession->consume($this->clock->now());
        $this->repository->save($checkoutSession);
    }
}
