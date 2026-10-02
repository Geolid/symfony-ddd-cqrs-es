<?php

declare(strict_types=1);

namespace Storefront\Tests\Browser;

use Storefront\Tests\Feature\SignIn\Component\IdentifyForm;

trait RegistrationExtension
{
    public function goToRegister(string $email): self
    {
        $this->visit('/signin');

        $this->use(static function (IdentifyForm $identify) use ($email): void {
            $identify->fillEmail($email)->submit();
        });

        $this->click('[data-testid="create-account-button"]');

        return $this;
    }
}
