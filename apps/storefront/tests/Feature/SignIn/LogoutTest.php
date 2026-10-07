<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class LogoutTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);
        $browser->interceptRedirects();

        // When
        $browser->visitRoute('_logout_main');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
        $browser->visitRoute('storefront_home_show')->assertNotSignedIn();
    }
}
