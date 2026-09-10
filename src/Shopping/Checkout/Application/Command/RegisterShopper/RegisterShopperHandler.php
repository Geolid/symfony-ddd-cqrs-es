<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\Command\RegisterShopper;

use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shopping\Checkout\Application\Command\RegisterShopper\Exception\ShopperEmailAlreadyInUseException;
use Shopping\Checkout\Application\ShopperUniqueKey;
use Shopping\Checkout\Domain\Exception\ShopperAlreadyExistsException;
use Shopping\Checkout\Domain\Repository\ShopperRepositoryInterface;
use Shopping\Checkout\Domain\Shopper;
use Shopping\Checkout\Domain\ValueObject\Email;
use Shopping\Checkout\Domain\ValueObject\ShopperId;

#[CommandHandler]
final readonly class RegisterShopperHandler
{
    public function __construct(
        private ShopperRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ShopperEmailAlreadyInUseException
     * @throws ShopperAlreadyExistsException
     */
    public function __invoke(RegisterShopper $command): void
    {
        $email = Email::fromString($command->email);
        $id = ShopperId::forIdentity($command->identityId);

        try {
            $this->uniqueValues->claim(UniqueKey::for(ShopperUniqueKey::EMAIL), $email->value, $id->toString());
        } catch (UniquenessViolatedException $e) {
            throw ShopperEmailAlreadyInUseException::forEmail($email->value, $e);
        }

        $shopper = Shopper::register(
            id: $id,
            identityId: $command->identityId,
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($shopper);
    }
}
