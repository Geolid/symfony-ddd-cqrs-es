<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Authentication\Support\Factory\PasswordCredentialFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Storefront\Tests\Support\Persona;

/**
 * @method static Persona persona()
 */
final class ResetRequestedStory extends AbstractIdentityStory
{
    /**
     * @throws UniquenessViolatedException
     */
    public function build(): void
    {
        $identity = IdentityFactory::new()->confirmed()->create();

        $this->addState('persona', $this->personaOf(
            $identity,
            static fn (PasswordCredentialFactory $credential): PasswordCredentialFactory => $credential->resetRequested(),
        ));
    }
}
