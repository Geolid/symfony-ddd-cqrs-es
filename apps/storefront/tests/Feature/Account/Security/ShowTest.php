<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Support\Story\AccountConfirmedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class ShowTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        // When
        $browser->visitRoute('storefront_account_security_show');

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="full-name-value"]', AccountConfirmedStory::fullName())
            ->assertSeeIn('[data-testid="email-value"]', AccountConfirmedStory::email());
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_show');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
