<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

use function Zenstruck\Foundry\faker;

/**
 * A confirmed account with a password and an enrolled TOTP.
 *
 * @method static string password()
 * @method static string totpSecret()
 */
final class AccountTwoFactorStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly TotpCipherInterface $cipher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $secret = faker()->totpSecret();

        $this->store(TotpCredentialFactory::new()->withIdentityId(AccountConfirmedStory::id())->withSecret($secret)->withCipher($this->cipher)->create());
        $this->addState('id', AccountConfirmedStory::id());
        $this->addState('email', AccountConfirmedStory::email());
        $this->addState('fullName', AccountConfirmedStory::fullName());
        $this->addState('password', AccountConfirmedStory::password());
        $this->addState('totpSecret', $secret);
    }
}
