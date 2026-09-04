<?php

declare(strict_types=1);

namespace Finance\Payer\Application\Command\RegisterPayerAddress;

use Finance\Payer\Domain\Exception\PayerAlreadyExistsException;
use Finance\Payer\Domain\Exception\PayerNotFoundException;
use Finance\Payer\Domain\Repository\PayerRepositoryInterface;
use Finance\Payer\Domain\ValueObject\PayerId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;

#[CommandHandler]
final readonly class RegisterPayerAddressHandler
{
    public function __construct(
        private PayerRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws PayerNotFoundException
     * @throws PayerAlreadyExistsException
     */
    public function __invoke(RegisterPayerAddress $command): void
    {
        $payer = $this->repository->load(PayerId::fromString($command->payerId));

        $payer->registerAddress(
            PostalAddress::of(
                $command->recipientName,
                Address::of($command->street, $command->postalCode, $command->city, $command->countryCode),
            ),
            $this->clock->now(),
        );

        $this->repository->save($payer);
    }
}
