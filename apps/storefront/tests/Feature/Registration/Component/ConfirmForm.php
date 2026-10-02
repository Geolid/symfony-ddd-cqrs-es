<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Registration\Component;

use Zenstruck\Browser\Component;

final class ConfirmForm extends Component
{
    public function fillCode(string $code): self
    {
        $this->browser()->fillField('confirmation_code', $code);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="confirm-form"] button[type="submit"]');

        return $this;
    }

    public function clickResend(): self
    {
        $this->browser()->click('[data-testid="confirm-resend-link"]');

        return $this;
    }

    public function assertInvalidCodeError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="confirmation_code-error"]', 'error_invalid');

        return $this;
    }

    public function assertAttemptsExceededError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="confirmation_code-error"]', 'error_attempts_exceeded');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="confirm-form"]');
    }
}
