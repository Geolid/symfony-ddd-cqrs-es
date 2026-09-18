<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity;

use Iam\Identity\Application\Command\RegisterIdentity\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\FullName;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

#[CommandHandler]
final readonly class RegisterIdentityHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityEmailAlreadyInUseException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(RegisterIdentity $command): void
    {
        $email = Email::fromString($command->email);

        try {
            $this->uniqueness->claim(UniqueKey::for(IdentityUniqueKey::EMAIL), $email->value, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw IdentityEmailAlreadyInUseException::forEmail($email->value, $e);
        }

        $identity = Identity::register(
            id: IdentityId::fromString($command->id),
            fullName: FullName::fromString($command->fullName),
            email: $email,
            registeredAt: $this->clock->now(),
        );

        $this->repository->save($identity);
    }
}
