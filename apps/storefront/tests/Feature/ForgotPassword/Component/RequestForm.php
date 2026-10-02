<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\ForgotPassword\Component;

use Zenstruck\Browser\Component;

final class RequestForm extends Component
{
    public function fillEmail(string $email): self
    {
        $this->browser()->fillField('password_reset_request_email', $email);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="forgot-password-request-form"] button[type="submit"]');

        return $this;
    }

    public function assertEmailNotFoundError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="password_reset_request_email-error"]', 'request_error_not_found');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="forgot-password-request-form"]');
    }
}
