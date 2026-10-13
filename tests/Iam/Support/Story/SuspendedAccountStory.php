<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * A confirmed identity with a password, suspended by a moderator.
 */
final class SuspendedAccountStory extends AbstractPasswordAccountStory
{
    public function build(): void
    {
        $this->persistWithPassword(IdentityFactory::new()->confirmed()->suspended());
    }
}
