<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Symfony\Component\Clock\Clock;

/**
 * @method static string password()
 */
final class AccountSuspendedPasswordResetRequestedStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly PasswordCredentialRepositoryInterface $credentials,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $credential = $this->credentials->load(PasswordCredentialId::forIdentity(AccountSuspendedStory::id()));
        $credential->requestReset(Clock::get()->now()->modify('+3 days'));

        $this->store($credential);
        $this->addState('id', AccountSuspendedStory::id());
        $this->addState('email', AccountSuspendedStory::email());
        $this->addState('fullName', AccountSuspendedStory::fullName());
        $this->addState('password', AccountSuspendedStory::password());
    }
}
