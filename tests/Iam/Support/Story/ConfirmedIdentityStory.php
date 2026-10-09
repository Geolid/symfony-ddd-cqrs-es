<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * A confirmed identity with no credential.
 */
final class ConfirmedIdentityStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();
        $this->persist($identity);
        $this->addState('account', new Account($identity->id->toString(), $identity->email->value, $identity->fullName->value));
    }
}
