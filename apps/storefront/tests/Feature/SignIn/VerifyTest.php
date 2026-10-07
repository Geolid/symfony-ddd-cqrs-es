<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\IdentityStoryTrait;

final class VerifyTest extends AbstractStorefrontTestCase
{
    use IdentityStoryTrait;

    #[Test]
    public function itShowsVerify(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();

        // When
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->use(static function (VerifyForm $verify) use ($credential): void {
                $verify->assertEmailPrefilled($credential->email);
            });
    }

    #[Test]
    public function itRedirectsToIdentifyWhenAccessedDirectly(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visitRoute('storefront_signin_verify');

        // Then
        $browser->assertRedirectedToRoute('storefront_signin_identify');
    }

    #[Test]
    public function itSignsIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($credential): void {
            $verify->fillPassword($credential->password)->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword('wrong password')->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify) use ($credential): void {
            $verify->assertInvalidCredentialsError()->assertEmailPrefilled($credential->email);
        });
    }

    #[Test]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->suspendedIdentity();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify) use ($credential): void {
            $verify->fillPassword($credential->password)->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertSuspendedError();
        });
    }

    #[Test]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $credential = $this->confirmedIdentity();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($credential): void {
            $identify->fillEmail($credential->email)->submit();
        });

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (VerifyForm $verify): void {
                $verify->fillPassword('wrong password')->submit();
            });
        }

        // When
        $browser->use(static function (VerifyForm $verify) use ($credential): void {
            $verify->fillPassword($credential->password)->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertThrottledError();
        });
    }
}
