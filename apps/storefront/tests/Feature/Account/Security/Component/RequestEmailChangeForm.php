<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security\Component;

use Zenstruck\Browser\Component;

final class RequestEmailChangeForm extends Component
{
    public function fillNewEmail(string $email): self
    {
        $this->browser()->fillField('request_email_change_newEmail', $email);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="request-email-change-submit"]');

        return $this;
    }

    public function assertAlreadyInUseError(): self
    {
        $this->browser()->assertSeeIn('[data-testid="request_email_change_newEmail-error"]', 'change_email_error_already_in_use');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="request-email-change-form"]');
    }
}
