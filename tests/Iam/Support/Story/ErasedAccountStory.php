<?php

declare(strict_types=1);

namespace Iam\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;

/**
 * An identity whose erasure went through; no credential.
 */
final class ErasedAccountStory extends AbstractAccountStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $this->persist($identity);
        $this->addState('account', new Account($identity->id->toString(), $identity->email->value, $identity->fullName->value));
    }
}
