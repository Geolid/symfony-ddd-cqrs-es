<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\IdentityUniqueValue;
use Iam\Identity\Domain\Login;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class RegisterIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws LoginAlreadyTakenException
     */
    public function __invoke(RegisterIdentity $command): void
    {
        $login = Login::fromString($command->login);
        $fingerprint = $login->fingerprint();

        try {
            $this->uniqueValues->reserve(IdentityUniqueValue::LOGIN, $fingerprint);
        } catch (UniqueValueAlreadyTakenException) {
            throw LoginAlreadyTakenException::forFingerprint($fingerprint);
        }

        $this->repository->save(Identity::register(
            IdentityId::fromString($command->id),
            $login,
            $this->clock->now(),
        ));
    }
}
