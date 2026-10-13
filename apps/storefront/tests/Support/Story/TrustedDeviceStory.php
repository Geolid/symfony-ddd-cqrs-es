<?php

declare(strict_types=1);

namespace Storefront\Tests\Support\Story;

use Iam\Tests\Authentication\Support\Factory\TrustedDeviceFactory;
use Storefront\Tests\Support\Persona;
use Support\Foundry\Story\AbstractAggregateStory;

/**
 * @method static Persona persona()
 */
final class TrustedDeviceStory extends AbstractAggregateStory
{
    public function build(): void
    {
        $persona = TwoFactorStory::persona();

        $this->store(TrustedDeviceFactory::new()->withIdentityId($persona->id)->create());
        $this->addState('persona', $persona);
    }
}
