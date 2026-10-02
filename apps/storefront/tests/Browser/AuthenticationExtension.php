<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;

trait AuthenticationExtension
{
    public function signInAs(string $email, string $password): self
    {
        $this->visit('/signin');

        $this->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });

        $this->use(static function (VerifyForm $verify) use ($password): void {
            $verify->fillPassword($password)->submit();
        });

        return $this;
    }

    public function assertSignedIn(): self
    {
        $this->assertSeeElement('[data-testid="account-menu"]');

        return $this;
    }

    public function assertNotSignedIn(): self
    {
        $this->assertSeeElement('[data-testid="signin-link"]');

        return $this;
    }
}
