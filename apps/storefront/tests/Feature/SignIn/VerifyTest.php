<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Support\Story\ConfirmedAccountStory;
use Iam\Tests\Support\Story\SuspendedAccountStory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class VerifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itShowsVerify(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();

        // When
        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });

        // Then
        $browser->assertSuccessful()
            ->use(static function (VerifyForm $verify) use ($account): void {
                $verify->assertEmailPrefilled($account->email);
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
    #[WithStory(ConfirmedAccountStory::class)]
    public function itSignsIn(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });
        $browser->interceptRedirects();

        // When
        $browser->use(static function (VerifyForm $verify) use ($account): void {
            $verify->fillPassword($account->password())->submit();
        });

        // Then
        $browser->assertRedirectedToRoute('storefront_home_show');
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRefusesIncorrectPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify): void {
            $verify->fillPassword('wrong password')->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify) use ($account): void {
            $verify->assertInvalidCredentialsError()->assertEmailPrefilled($account->email);
        });
    }

    #[Test]
    #[WithStory(SuspendedAccountStory::class)]
    public function itRefusesSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = SuspendedAccountStory::account();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });

        // When
        $browser->use(static function (VerifyForm $verify) use ($account): void {
            $verify->fillPassword($account->password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertSuspendedError();
        });
    }

    #[Test]
    #[WithStory(ConfirmedAccountStory::class)]
    public function itRefusesAfterTooManyAttempts(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $account = ConfirmedAccountStory::account();

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($account): void {
            $identify->fillEmail($account->email)->submit();
        });

        for ($i = 0; $i < 3; ++$i) {
            $browser->use(static function (VerifyForm $verify): void {
                $verify->fillPassword('wrong password')->submit();
            });
        }

        // When
        $browser->use(static function (VerifyForm $verify) use ($account): void {
            $verify->fillPassword($account->password())->submit();
        });

        // Then
        $browser->use(static function (VerifyForm $verify): void {
            $verify->assertThrottledError();
        });
    }
}
