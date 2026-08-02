<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SetPasswordCredential;

use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Login;
use Iam\Identity\Domain\PasswordCredential;
use Iam\Identity\Domain\PasswordCredentialId;
use Iam\Identity\Domain\PasswordCredentialUniqueValue;
use Iam\Identity\Domain\Repository\PasswordCredentialRepositoryInterface;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[AsCommandHandler]
final readonly class SetPasswordCredentialHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private SecretHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws LoginAlreadyTakenException
     */
    public function __invoke(SetPasswordCredential $command): void
    {
        $identityId = IdentityId::fromString($command->identityId);
        $id = PasswordCredentialId::fromString($identityId->toString());

        if ($this->repository->has($id)) {
            $credential = $this->repository->load($id);
            $credential->change($command->password, $this->hasher, $this->clock->now());
            $this->repository->save($credential);

            return;
        }

        $login = Login::fromString($command->login);
        $fingerprint = $login->fingerprint();

        try {
            $this->uniqueValues->reserve(PasswordCredentialUniqueValue::LOGIN, $fingerprint);
        } catch (UniqueValueAlreadyTakenException) {
            throw LoginAlreadyTakenException::forFingerprint($fingerprint);
        }

        $this->repository->save(PasswordCredential::set(
            $id,
            $identityId,
            $login,
            $command->password,
            $this->hasher,
            $this->clock->now(),
        ));
    }
}
