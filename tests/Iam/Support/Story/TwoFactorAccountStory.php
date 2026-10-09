<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Factory\TotpCredentialFactory;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

use function Zenstruck\Foundry\faker;

/**
 * The confirmed account with a password, plus an enrolled TOTP. Builds on ConfirmedAccountStory.
 */
final class TwoFactorAccountStory extends AbstractAccountStory
{
    public function __construct(
        RepositoryManager $repositories,
        private readonly TotpCipherInterface $cipher,
    ) {
        parent::__construct($repositories);
    }

    public function build(): void
    {
        $base = ConfirmedAccountStory::account();
        $secret = faker()->totpSecret();

        $this->persist(TotpCredentialFactory::new()->withIdentityId($base->id)->withSecret($secret)->withCipher($this->cipher)->create());
        $this->addState('account', new Account(
            $base->id,
            $base->email,
            $base->fullName,
            password: $base->password(),
            totpSecret: $secret,
        ));
    }
}
