<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Tests\Identity\Support\Factory\ReasonFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Symfony\Component\Clock\Clock;

/**
 * @method static string password()
 */
final class AccountSuspendedStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly IdentityRepositoryInterface $identities,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $identity = $this->identities->load(IdentityId::fromString(AccountConfirmedStory::id()));
        $identity->suspend(ReasonFactory::new()->create(), Clock::get()->now()->modify('+1 day'));

        $this->store($identity);
        $this->addIdentityStates($identity);
        $this->addState('password', AccountConfirmedStory::password());
    }
}
