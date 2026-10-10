<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Support\Story\AccountConfirmedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class LogoutTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itLogsOut(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->interceptRedirects();

        // When
        $browser->visitRoute('_logout_main');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');

        $browser->visitRoute('storefront_home_show')->assertNotSignedIn();
    }
}
