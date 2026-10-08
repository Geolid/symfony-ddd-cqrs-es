<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\SignIn\Component;

use Zenstruck\Browser\Component;

final class TwoFactorForm extends Component
{
    public function fillCode(string $code): self
    {
        $this->browser()->fillField('two-factor-code', $code);

        return $this;
    }

    public function checkTrustDevice(): self
    {
        $this->browser()->checkField('two-factor-trusted');

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="two-factor-submit"]');

        return $this;
    }

    public function assertInvalidCodeError(): self
    {
        $this->browser()->assertSeeElement('[data-testid="two-factor-error"]');

        return $this;
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="two-factor-form"]');
    }
}
