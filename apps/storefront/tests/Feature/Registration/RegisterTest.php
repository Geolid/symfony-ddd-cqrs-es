<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration;

use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class RegisterTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itRegisters(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $email = IdentityBuilder::sample('email')->value;
        $fullName = IdentityBuilder::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;
        $browser->goToRegister($email);

        // When
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // Then
        $browser
            ->assertSuccessful()
            ->assertSeeElement('[data-testid="confirm-form"]');

        $this
            ->mailer()
            ->assertSentEmailCount(1)
            ->assertEmailSentTo($email, 'Confirm your email address');
    }

    #[Test]
    public function itRefusesPasswordMismatch(): void
    {
        // Given
        $browser = $this->activeBrowser();

        $email = IdentityBuilder::sample('email')->value;
        $fullName = IdentityBuilder::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;
        $browser->goToRegister($email);

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

        $email = IdentityBuilder::sample('email')->value;
        $fullName = IdentityBuilder::sample('fullName')->value;
        $password = PasswordCredentialBuilder::sample('password')->value;
        $browser->goToRegister($email);
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // When
        $browser->visit('/register');
        $browser->use(static function (RegisterForm $register) use ($fullName, $password): void {
            $register->fillFullName($fullName)->fillPassword($password)->submit();
        });

        // Then
        $browser->use(static function (RegisterForm $register): void {
            $register->assertEmailTakenError();
        });
    }
}
