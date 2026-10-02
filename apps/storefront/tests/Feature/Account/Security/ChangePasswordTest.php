<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Feature\Account\Security\Component\ChangePasswordForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Zenstruck\Browser;

final class ChangePasswordTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $password = $this->signInAs($browser);
        $browser->visit('/account/security/password/change');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($password): void {
            $form->fillCurrentPassword($password)->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->assertRedirectedTo('/signin')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_password_flash_changed');
    }

    #[Test]
    public function itRefusesIncorrectCurrentPassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);
        $browser->visit('/account/security/password/change');

        // When
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->fillCurrentPassword('wrong password')->fillNewPassword('Flamingo-73-Juniper!')->submit();
        });

        // Then
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->assertInvalidCurrentPasswordError();
        });
    }

    #[Test]
    public function itRefusesSamePassword(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $password = $this->signInAs($browser);
        $browser->visit('/account/security/password/change');

        // When
        $browser->use(static function (ChangePasswordForm $form) use ($password): void {
            $form->fillCurrentPassword($password)->fillNewPassword($password)->submit();
        });

        // Then
        $browser->use(static function (ChangePasswordForm $form): void {
            $form->assertSamePasswordError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/password/change');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    private function signInAs(Browser&AuthenticationExtensionInterface $browser): string
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $this->store($identity, $passwordBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);

        return $passwordBuilder['password']->value;
    }
}
