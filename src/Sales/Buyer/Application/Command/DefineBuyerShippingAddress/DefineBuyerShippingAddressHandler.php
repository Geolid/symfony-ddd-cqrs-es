<?php

declare(strict_types=1);

namespace Sales\Buyer\Application\Command\DefineBuyerShippingAddress;

use Psr\Clock\ClockInterface;
use Sales\Buyer\Domain\Exception\BuyerAlreadyExistsException;
use Sales\Buyer\Domain\Exception\BuyerNotFoundException;
use Sales\Buyer\Domain\Repository\BuyerRepositoryInterface;
use Sales\Buyer\Domain\ValueObject\BuyerId;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class DefineBuyerShippingAddressHandler
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
    public function __invoke(DefineBuyerShippingAddress $command): void
    {
        $buyer = $this->repository->load(BuyerId::fromString($command->buyerId));

        $buyer->defineShippingAddress($this->toPostalAddress($command->shippingAddress), $this->clock->now());

        $this->repository->save($buyer);
    }

    /**
     * @param array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}} $address
     */
    private function toPostalAddress(array $address): PostalAddress
    {
        return PostalAddress::of($address['recipientName'], Address::of(...$address['address']));
    }
}
