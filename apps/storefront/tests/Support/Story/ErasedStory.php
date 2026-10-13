<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Storefront\Tests\Support\Persona;
use Support\Foundry\Story\AbstractAggregateStory;

/**
 * @method static Persona persona()
 */
final class ErasedStory extends AbstractAggregateStory
{
    public function build(): void
    {
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();

        $this->store($identity);
        $this->addState('persona', new Persona($identity->id->toString(), $identity->email->value, $identity->fullName->value));
    }
}
