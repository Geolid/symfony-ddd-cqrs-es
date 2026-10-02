<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword\Component;

use Zenstruck\Browser\Component;

final class ResetForm extends Component
{
    public function fillCode(string $code): self
    {
        $this->browser()->fillField('password_reset_code', $code);

        return $this;
    }

    public function fillNewPassword(string $password): self
    {
        $this->browser()->fillField('password_reset_newPassword_first', $password)
            ->fillField('password_reset_newPassword_second', $password);

        return $this;
    }

    public function fillMismatchedNewPassword(string $first, string $second): self
    {
        $this->browser()->fillField('password_reset_newPassword_first', $first)
            ->fillField('password_reset_newPassword_second', $second);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="reset-password-form"] button[type="submit"]');

        return $this;
    }

    public function clickResend(): self
    {
        $this->browser()->click('[data-testid="reset-password-resend-link"]');

        return $this;
    }

    public function assertInvalidCodeError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="password_reset_code-error"]', 'error_invalid');

        return $this;
    }

    public function assertAttemptsExceededError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="password_reset_code-error"]', 'error_attempts_exceeded');

        return $this;
    }

    public function assertPasswordMismatchError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="password_reset_newPassword_first-error"]', 'invalid_password_mismatch');

        return $this;
    }

    public function assertSamePasswordError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="password_reset_newPassword-error"]', 'reset_error_same_password');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="reset-password-form"]');
    }
}
