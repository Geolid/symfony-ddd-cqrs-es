<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Storefront\Tests\Support\Persona;

/**
 * @method static Persona persona()
 */
final class PendingStory extends AbstractIdentityStory
{
    /**
     * @throws UniquenessViolatedException
     */
    public function build(): void
    {
        $this->addState('persona', $this->personaOf(IdentityFactory::new()->confirmationRequested()->create()));
    }
}
