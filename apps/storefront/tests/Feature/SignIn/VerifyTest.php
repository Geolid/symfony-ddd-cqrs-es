<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Storefront\Tests\Support\Story\ConfirmedStory;
use Storefront\Tests\Support\Story\SuspendedStory;
use Zenstruck\Foundry\Attribute\WithStory;

final class VerifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itShowsVerify(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->use(static function (VerifyForm $verify) use ($persona): void {
                $verify->assertEmailPrefilled($persona->email);
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
    #[WithStory(ConfirmedStory::class)]
    public function itSignsIn(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($persona): void {
            $verify->fillPassword($persona->password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword('wrong password')->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify) use ($persona): void {
            $verify->assertInvalidCredentialsError()->assertEmailPrefilled($persona->email);
        });
    }

    #[Test]
    #[WithStory(SuspendedStory::class)]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $persona = SuspendedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify) use ($persona): void {
            $verify->fillPassword($persona->password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertSuspendedError();
        });
    }

    #[Test]
    #[WithStory(ConfirmedStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $persona = ConfirmedStory::persona();
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($persona): void {
            $identify->fillEmail($persona->email)->submit();
        });

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (VerifyForm $verify): void {
                $verify->fillPassword('wrong password')->submit();
            });
        }

        // When
        $browser->use(static function (VerifyForm $verify) use ($persona): void {
            $verify->fillPassword($persona->password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertThrottledError();
        });
    }
}
