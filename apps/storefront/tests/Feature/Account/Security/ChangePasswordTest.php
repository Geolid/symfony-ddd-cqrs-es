<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Support\Story\AccountConfirmedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangePasswordForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class ChangePasswordTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->visitRoute('storefront_account_security_change_password');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->fillCurrentPassword(AccountConfirmedStory::password())->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_password_flash_changed');
    }

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesIncorrectCurrentPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

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
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->signInAs(AccountConfirmedStory::email(), AccountConfirmedStory::password());

        $browser->visitRoute('storefront_account_security_change_password');

        // When
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->fillCurrentPassword(AccountConfirmedStory::password())->fillNewPassword(AccountConfirmedStory::password())->submit();
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
