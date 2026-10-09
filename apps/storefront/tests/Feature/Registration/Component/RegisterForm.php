<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration\Component;

use Zenstruck\Browser\Component;

final class RegisterForm extends Component
{
    public function fillFullName(string $fullName): self
    {
        $this->browser()->fillField('register_fullName', $fullName);

        return $this;
    }

    public function fillPassword(string $password): self
    {
        $this->browser()->fillField('register_password_first', $password)
            ->fillField('register_password_second', $password);

        return $this;
    }

    public function fillMismatchedPassword(string $first, string $second): self
    {
        $this->browser()->fillField('register_password_first', $first)
            ->fillField('register_password_second', $second);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="register-submit"]');

        return $this;
    }

    public function assertEmailTakenError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="register-error"]', 'error_email_already_in_use');

        return $this;
    }

    public function assertPasswordMismatchError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="register_password_first-error"]', 'invalid_password_mismatch');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="register-form"]');
    }
}
