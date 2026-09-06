<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\DefineBuyerPostalAddress;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class DefineBuyerPostalAddressHandler
{
    public function __construct(
        private BuyerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BuyerNotFoundException
     * @throws BuyerAlreadyExistsException
     */
    public function __invoke(DefineBuyerPostalAddress $command): void
    {
        $buyer = $this->repository->load(BuyerId::fromString($command->buyerId));

        $buyer->definePostalAddress(
            PostalAddress::of(
                $command->recipientName,
                Address::of($command->street, $command->postalCode, $command->city, $command->countryCode),
            ),
            $this->clock->now(),
        );

        $this->repository->save($buyer);
    }
}
