<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeTotp;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class RevokeTotpHandler
{
    public function __construct(
        private TotpCredentialRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TotpCredentialNotFoundException
     * @throws TotpCredentialOwnedByAnotherIdentityException
     * @throws TotpCredentialAlreadyExistsException
     */
    public function __invoke(RevokeTotp $command): void
    {
        $credential = $this->repository->load(TotpCredentialId::fromString($command->id));
        $credential->revoke($command->identityId, $this->clock->now());

        $this->repository->save($credential);

        $this->uniqueness->release(
            UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY),
            $credential->id->toString(),
        );
    }
}
