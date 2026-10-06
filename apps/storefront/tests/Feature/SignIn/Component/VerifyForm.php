<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn\Component;

use Zenstruck\Browser\Component;

final class VerifyForm extends Component
{
    public function fillPassword(string $password): self
    {
        $this->browser()->fillField('password', $password);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="verify-submit"]');

        return $this;
    }

    public function assertInvalidCredentialsError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="verify-error"]', 'Invalid credentials.');

        return $this;
    }

    public function assertSuspendedError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="verify-error"]', 'Your account is suspended.');

        return $this;
    }

    public function assertThrottledError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="verify-error"]', 'Too many failed login attempts');

        return $this;
    }

    public function assertEmailPrefilled(string $email): self
    {
        $this->browser()->assertElementAttributeContains('input[name="email"]', 'value', $email);

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="verify-form"]');
    }
}
