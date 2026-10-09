<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * A confirmed identity that can sign in with a password.
 */
final class ConfirmedAccountStory extends AbstractPasswordAccountStory
{
    public function build(): void
    {
        $this->persistWithPassword(IdentityFactory::new()->confirmed());
    }
}
