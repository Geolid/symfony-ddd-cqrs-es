<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RevokeApiKey;

use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialNotFoundException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;

#[AsCommandHandler]
final readonly class RevokeApiKeyHandler
{
    public function __construct(
        private ApiKeyCredentialRepositoryInterface $repository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ApiKeyCredentialNotFoundException
     * @throws ApiKeyCredentialAlreadyExistsException
     */
    public function __invoke(RevokeApiKey $command): void
    {
        $credential = $this->repository->load(ApiKeyCredentialId::fromString($command->id));
        $credential->revoke($this->clock->now());

        $this->repository->save($credential);
    }
}
