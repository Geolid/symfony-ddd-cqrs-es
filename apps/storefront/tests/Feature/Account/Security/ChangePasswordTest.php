<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Support\Story\ConfirmedAccountStory;
use Zenstruck\Foundry\Attribute\WithStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangePasswordForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class ChangePasswordTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_change_password');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($account): void {
            $form->fillCurrentPassword($account->password())->fillNewPassword('Flamingo-73-Juniper!')->submit();
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
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

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
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_change_password');

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($account): void {
            $form->fillCurrentPassword($account->password())->fillNewPassword($account->password())->submit();
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
