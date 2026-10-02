<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Feature\Account\Security\Component\ChangeFullNameForm;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class ChangeFullNameTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itChanges(): void
    {
        // Given
        $browser = $this->activeBrowser();
        $identityBuilder = IdentityBuilder::new()->confirmed();
        $identity = $identityBuilder->create();
        $passwordBuilder = PasswordCredentialBuilder::new()
            ->withIdentityId($identity->id->toString())
            ->withHasher($this->service(PasswordHasherInterface::class))
            ->withPasswordStrength($this->service(PasswordStrengthSpecificationInterface::class));
        $this->store($identity, $passwordBuilder->create());
        $browser->signInAs($identityBuilder['email']->value, $passwordBuilder['password']->value);
        $browser->visit('/account/security/name/change');
        $browser->interceptRedirects();

        // When
        $browser->use(static function (ChangeFullNameForm $form): void {
            $form->fillFullName('Jamie Rivers')->submit();
        });

        // Then
        $browser->assertRedirectedTo('/account/security')
            ->assertSeeIn('[data-testid="flash-success"]', 'change_full_name_flash_changed')
            ->assertSeeIn('[data-testid="full-name-value"]', 'Jamie Rivers');
    }

    #[Test]
    public function itRequiresAuthentication(): void
    {
        // Given
        $browser = $this->activeBrowser()->interceptRedirects();

        // When
        $browser->visit('/account/security/name/change');

        // Then
        $browser->assertRedirectedTo('/signin');
    }
}
