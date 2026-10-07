<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeFullNameForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class ChangeFullNameTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);
        $browser->visitRoute('storefront_account_security_change_full_name');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangeFullNameForm $form): void {
            $form->fillFullName('Jamie Rivers')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_show')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_full_name_flash_changed')
            ->assertSeeIn('[data-testid="full-name-value"]', 'Jamie Rivers');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_change_full_name');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
