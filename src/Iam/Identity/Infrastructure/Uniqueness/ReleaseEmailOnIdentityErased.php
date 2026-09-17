<?php

declare(strict_types=1);

namespace Iam\Identity\Infrastructure\Uniqueness;

use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Event\IdentityErased;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Infrastructure\Processor;

#[Processor('iam.identity.release_email_on_identity_erased')]
final readonly class ReleaseEmailOnIdentityErased
{
    public function __construct(private UniquenessRegistryInterface $uniqueness)
    {
    }

    #[Subscribe(IdentityErased::class)]
    public function __invoke(IdentityErased $event): void
    {
        $this->uniqueness->release(UniqueKey::for(IdentityUniqueKey::EMAIL), $event->id->toString());
    }
}
