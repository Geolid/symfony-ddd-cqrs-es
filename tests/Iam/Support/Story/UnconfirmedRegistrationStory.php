<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/** Registered, confirmation code requested, never confirmed; no credential. */
final class UnconfirmedRegistrationStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmationRequested()->create();

        $this->persist($identity);

        $this->addState('account', new Account($identity->id->toString(), $identity->email->value, $identity->fullName->value));
    }
}
