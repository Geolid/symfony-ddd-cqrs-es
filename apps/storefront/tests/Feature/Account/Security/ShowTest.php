<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class ShowTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itShows(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();
        $browser->signInAs($persona->email, $persona->password());

        // When
        $browser->visitRoute('storefront_account_security_show');

        // Then
        $browser->assertSuccessful()
            ->assertSeeIn('[data-testid="full-name-value"]', $persona->fullName)
            ->assertSeeIn('[data-testid="email-value"]', $persona->email);
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
