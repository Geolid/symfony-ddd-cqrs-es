<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Compliance\Tests\Erasing\Support\Factory\ErasureFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Storefront\Tests\Support\Persona;

/**
 * @method static Persona persona()
 */
final class ErasureRequestedStory extends AbstractIdentityStory
{
    /**
     * @throws UniquenessViolatedException
     */
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->erasureRequested()->create();

        $this->addState('persona', $this->personaOf($identity));
        $this->store(ErasureFactory::new()->withIdentityId($identity->id->toString())->create());
    }
}
