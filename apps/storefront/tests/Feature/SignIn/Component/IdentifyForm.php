<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn\Component;

use Zenstruck\Browser\Component;

final class IdentifyForm extends Component
{
    public function fillEmail(string $email): self
    {
        $this->browser()->fillField('identify_email', $email);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="identify-form"] button[type="submit"]');

        return $this;
    }

    public function assertEmailError(): self
    {
        $this->browser()->assertSeeElement('[data-testid="identify_email-error"]');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="identify-form"]');
    }
}
