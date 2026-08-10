<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Processor;

use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueValue;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\SyncProcessor;
use Shared\Domain\Service\UniqueValueRegistryInterface;

#[SyncProcessor('iam.identity.release_label_on_api_token_credential_revoked')]
final readonly class ReleaseLabelOnApiTokenCredentialRevoked
{
    public function __construct(
        private ApiTokenCredentialRepositoryInterface $repository,
        private UniqueValueRegistryInterface $uniqueValues,
    ) {
    }

    /**
     * @throws ApiTokenCredentialNotFoundException
     */
    #[Subscribe(ApiTokenCredentialRevoked::class)]
    public function __invoke(ApiTokenCredentialRevoked $event): void
    {
        $credential = $this->repository->load(ApiTokenCredentialId::fromString($event->id));

        $fingerprint = $credential->label()->fingerprintFor($credential->identityId()->toString());
        $this->uniqueValues->release(ApiTokenCredentialUniqueValue::LABEL, $fingerprint);
    }
}
