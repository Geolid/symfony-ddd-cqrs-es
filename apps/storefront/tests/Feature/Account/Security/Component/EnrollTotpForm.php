<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Account\Security\Component;

use Zenstruck\Browser\Component;

final class EnrollTotpForm extends Component
{
    public function fillCode(string $code): self
    {
        $this->browser()->fillField('two_factor_confirm_code', $code);

        return $this;
    }

    public function submit(): self
    {
        $this->browser()->click('[data-testid="enroll-totp-submit"]');

        return $this;
    }

    public function secret(): string
    {
        return str_replace(' ', '', $this->browser()->crawler()->filter('[data-testid="totp-secret"]')->text());
    }

    protected function preAssertions(): void
    {
        $this->browser()->assertSeeElement('[data-testid="enroll-totp-form"]');
    }
}
