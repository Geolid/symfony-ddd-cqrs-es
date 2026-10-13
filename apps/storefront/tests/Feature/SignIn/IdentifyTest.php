<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class IdentifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsIdentify(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_signin_identify');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="identify-form"]');
    }

    #[Test]
    public function itShowsCreateAccount(): void
    {
        // Given
        $browser = $this->activeBrowser()->visitRoute('storefront_signin_identify');

        // When
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(IdentityFactory::sample('email')->value)->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="create-account-button"]');
    }

    #[Test]
    public function itRedirectsToConfirmation(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->create();

        // When
        $browser->visitRoute('storefront_signin_identify')
            ->use(static function (IdentifyForm $identify) use ($account): void {
                $identify->fillEmail($account->email)->submit();
            });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $account->id]);
    }

    #[Test]
    public function itRedirectsToVerify(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $account = $this->account()->confirmed()->create();

        // When
        $browser->visitRoute('storefront_signin_identify')
            ->use(static function (IdentifyForm $identify) use ($account): void {
                $identify->fillEmail($account->email)->submit();
            });

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_verify');
    }

    #[Test]
    public function itRefusesMalformedEmail(): void
    {
        // Given
        $browser = $this->activeBrowser()->visitRoute('storefront_signin_identify');

        // When
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail('not-an-email')->submit();
        });

        // Then
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->assertEmailError();
        });
    }
}
