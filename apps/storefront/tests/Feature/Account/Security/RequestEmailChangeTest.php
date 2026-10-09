<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Tests\Authentication\Support\Factory\PasswordFactory;
use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Identity\Support\Factory\FullNameFactory;
use Iam\Tests\Support\Story\ConfirmedAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class RequestEmailChangeTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        // When
        $browser->visitRoute('storefront_account_security_request_email_change');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-email-change-form"]');
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRequests(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();
        $browser->signInAs($account->email, $account->password());

        $browser->visitRoute('storefront_account_security_request_email_change');
        $browser->interceptRedirects();
        $newEmail = EmailFactory::new()->create()->value;

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_account_security_change_email');
        $this->assertEmailSent(1, $newEmail, 'Confirm your new email address');
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRefusesEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $otherEmail = EmailFactory::new()->create()->value;
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($otherEmail): void {
            $identify->fillEmail($otherEmail)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');
        $browser->use(static function (RegisterForm $register): void {
            $register->fillFullName(FullNameFactory::new()->create()->value)
                ->fillPassword(PasswordFactory::new()->create()->value)
                ->submit();
        });

        $account = ConfirmedAccountStory::account();
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
