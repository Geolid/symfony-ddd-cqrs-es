<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * Registered, no confirmation code requested, no credential.
 */
final class RegisteredAccountStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->create();
        $this->persist($identity);
        $this->addState('account', new Account($identity->id->toString(), $identity->email->value, $identity->fullName->value));
    }
}
