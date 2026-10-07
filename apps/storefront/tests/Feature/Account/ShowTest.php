<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStoryTrait;

final class ShowTest extends AbstractStorefrontTestCase
{
    use IdentityStoryTrait;

    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        // When
        $browser->visitRoute('storefront_account_show');

        // Then
        $browser->assertSuccessful();
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_show');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
