<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

final class AccountWithoutPasswordStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();

        $this->store($identity);
        $this->addIdentityStates($identity);
    }
}
