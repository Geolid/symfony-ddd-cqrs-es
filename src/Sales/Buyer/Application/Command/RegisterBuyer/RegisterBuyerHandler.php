<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RegisterBuyer;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Application\Command\RegisterBuyer\Exception\BuyerEmailAlreadyTakenException;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\BuyerUniqueKey;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;

#[CommandHandler]
final readonly class RegisterBuyerHandler
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerEmailAlreadyTakenException
     * @throws BuyerAlreadyExistsException
     */
    public function __invoke(RegisterBuyer $command): void
    {
        $email = Email::fromString($command->email);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(BuyerUniqueKey::EMAIL), $email->value, $command->id);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw BuyerEmailAlreadyTakenException::forEmail($email->value, $e);
        }

        $buyer = Buyer::register(
            id: BuyerId::fromString($command->id),
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($buyer);
    }
}
