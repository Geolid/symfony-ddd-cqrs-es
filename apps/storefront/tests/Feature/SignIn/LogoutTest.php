<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class LogoutTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itLogsOut(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->interceptRedirects();

        // When
        $browser->visitRoute('_logout_main');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');

        $browser->visitRoute('storefront_home_show')->assertNotSignedIn();
    }
}
