<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Identity\Support\Factory\EmailFactory;
use Iam\Tests\Support\Story\ConfirmedAccountStory;
use Iam\Tests\Support\Story\RegisteredAccountStory;
use Iam\Tests\Support\Story\SuspendedAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class RequestTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsRequest(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_forgot_password_request');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="forgot-password-request-form"]');
    }

    #[Test]
    #[WithStory(RegisteredAccountStory::class)]
    public function itRedirectsToConfirmWhenUnconfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = RegisteredAccountStory::account();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $account->id]);
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRedirectsToReset(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = ConfirmedAccountStory::account();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $account->id]);
        $this->assertEmailSent(1, $account->email, 'Reset your password');
    }

    #[Test]
    public function itRefusesUnknownEmail(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(EmailFactory::new()->create()->value)->submit();
        });

        // Then
        $browser->use(static function (RequestForm $request): void {
            $request->assertEmailNotFoundError();
        });
    }

    #[Test]
    #[WithStory(SuspendedAccountStory::class)]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = SuspendedAccountStory::account();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($account): void {
            $request->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }
}
