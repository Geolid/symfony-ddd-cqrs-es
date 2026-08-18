<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Processor;

use Iam\Identity\Domain\Event\ApiTokenCredentialRevoked;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Repository\ApiTokenCredentialRepositoryInterface;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialId;
use Iam\Identity\Domain\ValueObject\ApiTokenCredentialUniqueKey;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Processor\Processor;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;

#[Processor('iam.identity.release_label_on_api_token_credential_revoked', sync: true)]
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

        $this->uniqueValues->release(
            UniqueKey::for(ApiTokenCredentialUniqueKey::LABEL, $credential->identityId->toString()),
            $credential->label->value,
            $credential->id->toString(),
        );
    }
}
