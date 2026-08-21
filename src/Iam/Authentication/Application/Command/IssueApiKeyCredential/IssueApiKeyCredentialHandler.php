<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueApiKeyCredential;

use Iam\Authentication\Application\Exception\AuthenticatableIdentityResultNotFoundException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Finder\AuthenticatableIdentity\AuthenticatableIdentityFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ApiKeyCredential;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialAlreadyExistsException;
use Iam\Authentication\Domain\ApiKeyCredential\Repository\ApiKeyCredentialRepositoryInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialId;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\ApiKeyCredentialUniqueKey;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\AsCommandHandler;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\UniqueKey;

#[AsCommandHandler]
final readonly class IssueApiKeyCredentialHandler
{
    public function __construct(
        private AuthenticatableIdentityFinderInterface $authenticatableIdentityFinder,
        private ApiKeyCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
        private ApiKeyHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws AuthenticatableIdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws LabelAlreadyTakenException
     * @throws ApiKeyCredentialAlreadyExistsException
     */
    public function __invoke(IssueApiKeyCredential $command): void
    {
        if (!$this->authenticatableIdentityFinder->ofIdentityId($command->identityId)->authenticatable) {
            throw IdentityNotAuthenticatableException::forIdentity($command->identityId);
        }

        $id = ApiKeyCredentialId::fromString($command->id);
        $label = Label::fromString($command->label);

        try {
            $this->uniqueValues->reserve(UniqueKey::for(ApiKeyCredentialUniqueKey::LABEL, $command->identityId), $label->value, $id->toString(), $command->identityId);
        } catch (UniqueValueAlreadyTakenException $e) {
            throw LabelAlreadyTakenException::forLabel($label->value, $e);
        }

        $credential = ApiKeyCredential::issue(
            id: $id,
            identityId: $command->identityId,
            label: $label,
            keyId: KeyId::fromString($command->keyId),
            secret: $command->secret,
            hasher: $this->hasher,
            issuedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
