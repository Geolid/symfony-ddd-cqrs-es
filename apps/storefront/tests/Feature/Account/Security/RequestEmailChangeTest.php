<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class RequestEmailChangeTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-email-change-form"]');
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->interceptRedirects();
        $newEmail = IdentityFactory::sample('email')->value;

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email');
        $this->assertEmailSent(1, $newEmail, 'Confirm your new email address');
    }

    #[Test]
    public function itRefusesEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $otherEmail = IdentityFactory::sample('email')->value;
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($otherEmail): void {
            $identify->fillEmail($otherEmail)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');
        $browser->use(static function (RegisterForm $register): void {
            $register->fillFullName(IdentityFactory::sample('fullName')->value)
                ->fillPassword(PasswordFactory::new()->create()->value)
                ->submit();
        });

        $account = $this->account()->confirmed()->withPassword()->create();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_request_email_change');

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($otherEmail): void {
            $form->fillNewEmail($otherEmail)->submit();
        });

        // Then
        $browser->use(static function (RequestEmailChangeForm $form): void {
            $form->assertAlreadyInUseError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }
}
