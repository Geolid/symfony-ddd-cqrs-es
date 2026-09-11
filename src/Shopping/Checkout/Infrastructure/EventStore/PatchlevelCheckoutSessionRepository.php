<?php

declare(strict_types=1);

namespace Shopping\Checkout\Infrastructure\EventStore;

use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionAlreadyExistsException;
use Shopping\Checkout\Domain\CheckoutSession\Exception\CheckoutSessionNotFoundException;
use Shopping\Checkout\Domain\CheckoutSession\Repository\CheckoutSessionRepositoryInterface;
use Shopping\Checkout\Domain\CheckoutSession\ValueObject\CheckoutSessionId;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelCheckoutSessionRepository implements CheckoutSessionRepositoryInterface
{
    /**
     * @param Repository<CheckoutSession> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.shopping.checkout.checkout_session.repository')]
        private Repository $repository,
    ) {
    }

    public function has(CheckoutSessionId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(CheckoutSessionId $id): CheckoutSession
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw CheckoutSessionNotFoundException::forId($id->toString());
        }
    }

    public function save(CheckoutSession $checkoutSession): void
    {
        try {
            $this->repository->save($checkoutSession);
        } catch (AggregateAlreadyExists) {
            throw CheckoutSessionAlreadyExistsException::forId($checkoutSession->id->toString());
        }
    }
}
