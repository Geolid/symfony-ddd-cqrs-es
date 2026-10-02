<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class IdentifyTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itShowsIdentify(): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visit('/signin');

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="identify-form"]');
    }

    #[Test]
    public function itShowsCreateAccount(): void
    {
        // Given
        $browser = $this->activeBrowser()->visit('/signin');

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
        $given = $this->givenUnconfirmedIdentity();

        // When
        $browser->visit('/signin')
            ->use(static function (IdentifyForm $identify) use ($given): void {
                $identify->fillEmail($given->email)->submit();
            });

        // Then
        $browser->assertRedirectedTo($this->path('storefront_registration_confirm', ['identityId' => $given->id]));
    }

    #[Test]
    public function itRedirectsToVerify(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();
        $given = $this->givenConfirmedIdentity();

        // When
        $browser->visit('/signin')
            ->use(static function (IdentifyForm $identify) use ($given): void {
                $identify->fillEmail($given->email)->submit();
            });

        // Then
        $browser->assertRedirectedTo($this->path('storefront_signin_verify'));
    }

    #[Test]
    public function itRefusesMalformedEmail(): void
    {
        // Given
        $browser = $this->activeBrowser()->visit('/signin');

        // When
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->fillEmail('not-an-email')->submit();
        });

        // Then
        $browser->use(static function (IdentifyForm $identify): void {
            $identify->assertEmailError();
        });
    }

    private function givenUnconfirmedIdentity(): GivenIdentity
    {
        $identityBuilder = IdentityBuilder::new();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new GivenIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }

    private function givenConfirmedIdentity(): GivenIdentity
    {
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $this->store($identity);

        return new GivenIdentity($identity->id->toString(), $identityBuilder['email']->value);
    }
}

/**
 * @internal
 */
final readonly class GivenIdentity
{
    public function __construct(
        public string $id,
        public string $email,
    ) {
    }
}
