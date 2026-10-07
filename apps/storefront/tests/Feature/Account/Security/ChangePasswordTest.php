<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangePasswordForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class ChangePasswordTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_change_password');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($credential): void {
            $form->fillCurrentPassword($credential->password)->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_password_flash_changed');
    }

    #[Test]
    public function itRefusesIncorrectCurrentPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_change_password');

        // When
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->fillCurrentPassword('wrong password')->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->assertInvalidCurrentPasswordError();
        });
    }

    #[Test]
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();
        $browser->signInAs($credential->email, $credential->password);

        $browser->visitRoute('storefront_account_security_change_password');

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($credential): void {
            $form->fillCurrentPassword($credential->password)->fillNewPassword($credential->password)->submit();
        });

        // Then
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->assertSamePasswordError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_change_password');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
