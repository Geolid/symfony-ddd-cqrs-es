<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

final class AccountRegisteredStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->create();

        $this->store($identity);
        $this->addIdentityStates($identity);
    }
}
