<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Browser\AuthenticationExtensionInterface;
use Storefront\Tests\Feature\Account\Security\Component\RequestEmailChangeForm;
use Storefront\Tests\Feature\Registration\Component\RegisterForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\Clock\Clock;
use Zenstruck\Browser;

final class RequestEmailChangeTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShows(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);

        // When
        $browser->visit('/account/security/email/change');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="request-email-change-form"]');
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $this->signInAs($browser);
        $browser->visit('/account/security/email/change');
        $browser->interceptRedirects();
        $newEmail = IdentityBuilder::sample('email')->value;

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($newEmail): void {
            $form->fillNewEmail($newEmail)->submit();
        });

        // Then
        $browser->assertRedirectedTo('/account/security/email/change/confirm');
    }

    #[Test]
    public function itRefusesEmailAlreadyInUse(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $otherEmail = IdentityBuilder::sample('email')->value;
        $browser->goToRegister($otherEmail);
        $browser->use(static function (RegisterForm $register): void {
            $register->fillFullName(IdentityBuilder::sample('fullName')->value)
                ->fillPassword(PasswordCredentialBuilder::sample('password')->value)
                ->submit();
        });
        $this->signInAs($browser);
        $browser->visit('/account/security/email/change');

        // When
        $browser->use(static function (RequestEmailChangeForm $form) use ($otherEmail): void {
            $form->fillNewEmail($otherEmail)->submit();
        });

        // Then
        $browser->use(static function (RequestEmailChangeForm $form): void {
            $form->assertAlreadyInUseError();
        });
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/email/change');

        // Then
        $browser->assertRedirectedTo('/signin');
    }

    private function signInAs(Browser&AuthenticationExtensionInterface $browser): void
    {
        $identityBuilder = IdentityBuilder::new()->withRegisteredAt(Clock::get()->now()->modify('-1 hour'))->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $this->store($identity, $passwordBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);
    }
}
