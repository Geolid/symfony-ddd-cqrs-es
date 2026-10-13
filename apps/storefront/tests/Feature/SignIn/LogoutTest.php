<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class LogoutTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itLogsOut(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        $browser->interceptRedirects();

        // When
        $browser->visitRoute('_logout_main');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');

        $browser->visitRoute('storefront_home_show')->assertNotSignedIn();
    }
}
