<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn;

use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Tests\Authentication\Support\Builder\PasswordCredentialBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;

final class LogoutTest extends AbstractStorefrontTestCase
{
    #[Test]
    public function itLogsOut(): void
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
        $browser->interceptRedirects();

        // When
        $browser->visit('/logout');

        // Then
        $browser->assertRedirectedTo('/signin');
        $browser->visit('/home')->assertNotSignedIn();
    }
}
