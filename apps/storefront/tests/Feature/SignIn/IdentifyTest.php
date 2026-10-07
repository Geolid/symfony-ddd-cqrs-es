<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStory;

final class IdentifyTest extends AbstractStorefrontTestCase
{
    use IdentityStory;

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
            $identify->fillEmail(IdentityBuilder::sample('email')->value)->submit();
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
        $identity = $this->unconfirmedIdentity();

        // When
        $browser->visitRoute('storefront_signin_identify')
            ->use(static function (IdentifyForm $identify) use ($identity): void {
                $identify->fillEmail($identity->email)->submit();
            });

        // Then
        $browser->assertRedirectedToRoute('storefront_registration_confirm', ['identityId' => $identity->id]);
    }

    #[Test]
    public function itRedirectsToVerify(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identity = $this->confirmedIdentityWithoutCredential();

        // When
        $browser->visitRoute('storefront_signin_identify')
            ->use(static function (IdentifyForm $identify) use ($identity): void {
                $identify->fillEmail($identity->email)->submit();
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
