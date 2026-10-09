<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser\Capability;

use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;
use Storefront\Tests\Feature\SignIn\Component\VerifyForm;

trait AuthenticationTrait
{
    public function signInAs(string $email, string $password): self
    {
        $this->visitRoute('storefront_signin_identify');

        $this->use(static function (IdentifyForm $identifyForm) use ($email): void {
            $identifyForm->fillEmail($email)->submit();
        });

        $this->use(static function (VerifyForm $verifyForm) use ($password): void {
            $verifyForm->fillPassword($password)->submit();
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

    abstract public function visitRoute(string $route, array $params = []): self;
}
