<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * A suspended identity with a password, which asked for a reset it has not used yet.
 */
final class SuspendedPasswordResetRequestedAccountStory extends AbstractPasswordAccountStory
{
    public function build(): void
    {
        $this->persistWithPassword(
            IdentityFactory::new()->confirmed()->suspended(),
            static fn (PasswordCredentialFactory $credential): PasswordCredentialFactory => $credential->resetRequested(),
        );
    }
}
