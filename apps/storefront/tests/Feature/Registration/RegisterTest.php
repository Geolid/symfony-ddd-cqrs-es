<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class RegisterTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $email = IdentityFactory::sample('email')->value;
        $fullName = IdentityFactory::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');

        // When
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // Then
        $browser
            ->assertSuccessful()
            ->assertSeeElement('[data-testid="confirm-form"]');

        $this->assertEmailSent(1, $email, 'Confirm your email address');
    }

    #[Test]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $email = IdentityFactory::sample('email')->value;
        $fullName = IdentityFactory::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');

        // When
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)
                ->fillMismatchedPassword($password, 'Different-99-Value!')
                ->submit();
        });

        // Then
        $browser->use(static function (RegisterForm $register): void {
            $register->assertPasswordMismatchError();
        });
    }

    #[Test]
    public function itRefusesWhenEmailAlreadyRegistered(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $email = IdentityFactory::sample('email')->value;
        $fullName = IdentityFactory::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;

        $browser->visitRoute('storefront_signin_identify');
        $browser->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });
        $browser->click('[data-testid="create-account-button"]');

        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // When
        $browser->visitRoute('storefront_registration_register');
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // Then
        $browser->use(static function (RegisterForm $register): void {
            $register->assertEmailTakenError();
        });
    }
}
