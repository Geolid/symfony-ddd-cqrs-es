<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\EnrollTotp;

use Iam\Authentication\Application\AuthenticationUniqueKey;
use Iam\Authentication\Application\Command\EnrollTotp\Exception\TotpAlreadyEnrolledException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class EnrollTotpHandler
{
    public function __construct(
        private TotpCredentialRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private TotpCipherInterface $cipher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TotpAlreadyEnrolledException
     * @throws TotpCredentialAlreadyExistsException
     */
    public function __invoke(EnrollTotp $command): void
    {
        try {
            $this->uniqueness->claim(UniqueKey::for(AuthenticationUniqueKey::TOTP_CREDENTIAL_IDENTITY), $command->identityId, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw TotpAlreadyEnrolledException::forIdentity($command->identityId, $e);
        }

        $credential = TotpCredential::enroll(
            id: TotpCredentialId::fromString($command->id),
            identityId: $command->identityId,
            secret: $command->secret,
            cipher: $this->cipher,
            enrolledAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
