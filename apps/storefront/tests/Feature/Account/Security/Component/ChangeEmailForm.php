<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security\Component;

use Zenstruck\Browser\Component;

final class ChangeEmailForm extends Component
{
    public function fillCode(string $code): self
    {
        $this->browser()->fillField('change_email_code', $code);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="change-email-form"] button[type="submit"]');

        return $this;
    }

    public function clickResend(): self
    {
        $this->browser()->click('[data-testid="change-email-resend-link"]');

        return $this;
    }

    public function assertInvalidCodeError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="change_email_code-error"]', 'error_invalid');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="change-email-form"]');
    }
}
