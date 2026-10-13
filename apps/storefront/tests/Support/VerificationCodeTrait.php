<?php

declare(strict_types=1);

namespace Storefront\Tests\Support;

use Zenstruck\Mailer\Test\TestMailer;

trait VerificationCodeTrait
{
    abstract protected function mailer(): TestMailer;

    protected function verificationCode(): string
    {
        $body = $this->mailer()->sentEmails()->last()->getTextBody();
        \assert(\is_string($body));

        $matched = preg_match('/(\d{6})/', $body, $matches);
        \assert(1 === $matched);

        return $matches[1];
    }
}
