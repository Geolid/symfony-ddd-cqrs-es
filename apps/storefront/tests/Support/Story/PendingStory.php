<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Storefront\Tests\Support\Persona;
use Symfony\Component\Clock\Clock;

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
        $now = Clock::get()->now();
        $identity = IdentityFactory::new()
            ->withRegisteredAt($now->modify('-1 hour'))
            ->confirmationRequested($now->modify('-30 minutes'))
            ->create();

        $this->addState('persona', $this->personaOf($identity));
    }
}
