<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\TotpCredential;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelTotpCredentialRepository implements TotpCredentialRepositoryInterface
{
    /**
     * @param Repository<TotpCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.totp_credential.repository')]
        private Repository $repository,
    ) {
    }

    public function has(TotpCredentialId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(TotpCredentialId $id): TotpCredential
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw TotpCredentialNotFoundException::forId($id->toString());
        }
    }

    public function save(TotpCredential $totpCredential): void
    {
        try {
            $this->repository->save($totpCredential);
        } catch (AggregateAlreadyExists) {
            throw TotpCredentialAlreadyExistsException::forId($totpCredential->id->toString());
        }
    }
}
