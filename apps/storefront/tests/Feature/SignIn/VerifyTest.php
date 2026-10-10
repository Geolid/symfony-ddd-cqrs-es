<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Support\Story\AccountConfirmedStory;
use Iam\Tests\Support\Story\AccountSuspendedStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class VerifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itShowsVerify(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountConfirmedStory::email())->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->use(static function (VerifyForm $verify): void {
                $verify->assertEmailPrefilled(AccountConfirmedStory::email());
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
    #[WithStory(AccountConfirmedStory::class)]
    public function itSignsIn(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountConfirmedStory::email())->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword(AccountConfirmedStory::password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountConfirmedStory::email())->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword('wrong password')->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertInvalidCredentialsError()->assertEmailPrefilled(AccountConfirmedStory::email());
        });
    }

    #[Test]
    #[WithStory(AccountSuspendedStory::class)]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountSuspendedStory::email())->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword(AccountSuspendedStory::password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertSuspendedError();
        });
    }

    #[Test]
    #[WithStory(AccountConfirmedStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail(AccountConfirmedStory::email())->submit();
        });

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (VerifyForm $verify): void {
                $verify->fillPassword('wrong password')->submit();
            });
        }

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword(AccountConfirmedStory::password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertThrottledError();
        });
    }
}
