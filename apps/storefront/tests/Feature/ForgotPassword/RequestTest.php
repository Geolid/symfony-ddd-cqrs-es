<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Tests\Identity\Support\Factory\EmailFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\PendingStory;
use Storefront\Tests\Support\Story\SuspendedStory;
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
    #[WithStory(PendingStory::class)]
    public function itRedirectsToConfirmWhenUnconfirmed(): void
    {
        // Given
        $persona = PendingStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($persona): void {
            $request->fillEmail($persona->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $persona->id]);
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRedirectsToReset(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($persona): void {
            $request->fillEmail($persona->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_forgot_password_reset', ['identityId' => $persona->id]);
        $this->assertEmailSent(1, $persona->email, 'Reset your password');
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
    #[WithStory(SuspendedStory::class)]
    public function itRefusesWhenSuspended(): void
    {
        // Given
        $persona = SuspendedStory::persona();
        $browser = $this->activeBrowser()->interceptRedirects();

        $browser->visitRoute('storefront_forgot_password_request');

        // When
        $browser->use(static function (RequestForm $request) use ($persona): void {
            $request->fillEmail($persona->email)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify')
            ->assertSeeIn('[data-testid="flash-error"]', 'reset_flash_not_authenticatable');
    }
}
