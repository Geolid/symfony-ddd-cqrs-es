<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * An account whose erasure went through.
 */
final class AccountErasedStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();

        $this->store($identity);
        $this->addIdentityStates($identity);
    }
}
