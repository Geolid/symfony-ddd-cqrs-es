<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Identity\Domain\Identity;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\ForgotPassword\Component\RequestForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class RequestTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsRequest(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visit('/forgot-password');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="forgot-password-request-form"]');
    }

    #[Test]
    public function itRedirectsToConfirmWhenUnconfirmed(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityBuilder = IdentityBuilder::new();
        $identity = $identityBuilder->create();
        $this->store($identity);
        $browser->visit('/forgot-password');

        // When
        $browser->use(static function (RequestForm $request) use ($identityBuilder): void {
            $request->fillEmail($identityBuilder['email']->value)->submit();
        });

        // Then
        $browser->assertRedirectedTo("/register/{$identity->id->toString()}/confirm");
    }

    #[Test]
    public function itRedirectsToReset(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $this->store($identity, $this->passwordCredentialFor($identity)->create());
        $browser->visit('/forgot-password');

        // When
        $browser->use(static function (RequestForm $request) use ($identityBuilder): void {
            $request->fillEmail($identityBuilder['email']->value)->submit();
        });

        // Then
        $browser->assertRedirectedTo("/forgot-password/{$identity->id->toString()}/reset");
    }

    #[Test]
    public function itRefusesUnknownEmail(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $browser->visit('/forgot-password');

        // When
        $browser->use(static function (RequestForm $request): void {
            $request->fillEmail(IdentityBuilder::sample('email')->value)->submit();
        });

        // Then
        $browser->use(static function (RequestForm $request): void {
            $request->assertEmailNotFoundError();
        });
    }

    #[Test]
    public function itRejectsSuspendedAccount(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityBuilder = IdentityBuilder::new()->confirmed()->suspended();
        $identity = $identityBuilder->create();
        $this->store($identity, $this->passwordCredentialFor($identity)->create());
        $browser->visit('/forgot-password');

        // When
        $browser->use(static function (RequestForm $request) use ($identityBuilder): void {
            $request->fillEmail($identityBuilder['email']->value)->submit();
        });

        // Then
        $browser->assertStatus(409);
    }

    private function passwordCredentialFor(Identity $identity): PasswordCredentialBuilder
    {
        return PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
    }
}
