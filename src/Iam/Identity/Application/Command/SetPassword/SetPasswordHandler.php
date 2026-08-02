<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SetPassword;

use Iam\Identity\Domain\IdentityId;
use Iam\Identity\Domain\Password;
use Iam\Identity\Domain\PasswordId;
use Iam\Identity\Domain\Repository\PasswordRepositoryInterface;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class SetPasswordHandler
{
    public function __construct(
        private PasswordRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(SetPassword $command): void
    {
        $identityId = IdentityId::fromString($command->identityId);
        $id = PasswordId::fromString($identityId->toString());

        if ($this->repository->has($id)) {
            $password = $this->repository->load($id);
            $password->change($command->hash, $this->clock->now());
        } else {
            $password = Password::set($id, $identityId, $command->hash, $this->clock->now());
        }

        $this->repository->save($password);
    }
}
