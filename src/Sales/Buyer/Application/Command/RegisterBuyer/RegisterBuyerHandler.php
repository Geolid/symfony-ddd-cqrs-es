<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\RegisterBuyer;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Application\Uniqueness\BuyerUniqueKey;
use Sales\Buyer\Application\Uniqueness\Exception\BuyerEmailAlreadyInUseException;
use Sales\Buyer\Domain\Buyer;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Sales\Buyer\Domain\ValueObject\Email;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class RegisterBuyerHandler
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerEmailAlreadyInUseException
     * @throws BuyerAlreadyExistsException
     */
    public function __invoke(RegisterBuyer $command): void
    {
        $email = Email::fromString($command->email);
        $id = BuyerId::forIdentity($command->identityId);

        try {
            $this->uniqueValues->claim(UniqueKey::for(BuyerUniqueKey::EMAIL), $email->value, $id->toString());
        } catch (UniquenessViolatedException $e) {
            throw BuyerEmailAlreadyInUseException::forEmail($email->value, $e);
        }

        $buyer = Buyer::register(
            id: $id,
            identityId: $command->identityId,
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($buyer);
    }
}
