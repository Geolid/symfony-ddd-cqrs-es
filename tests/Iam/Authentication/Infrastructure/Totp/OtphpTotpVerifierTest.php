<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\Totp;

use Iam\Authentication\Infrastructure\Totp\OtphpTotpVerifier;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Clock;

final class OtphpTotpVerifierTest extends TestCase
{
    private OtphpTotpVerifier $verifier;
    private string $secret;
    private string $validCode;
    private string $invalidCode;

    protected function setUp(): void
    {
        parent::setUp();

        $clock = Clock::get();
        $this->secret = TOTP::generate()->getSecret();
        $this->validCode = TOTP::createFromSecret($this->secret, $clock)->now();
        $this->invalidCode = str_pad((string) (((int) $this->validCode + 1) % 1_000_000), 6, '0', \STR_PAD_LEFT);
        $this->verifier = new OtphpTotpVerifier($clock);
    }

    #[Test]
    public function itVerifies(): void
    {
        // When
        $verified = $this->verifier->verify($this->secret, $this->validCode);

        // Then
        self::assertTrue($verified);
    }

    #[Test]
    public function itDoesNotVerifyAnIncorrectCode(): void
    {
        // When
        $verified = $this->verifier->verify($this->secret, $this->invalidCode);

        // Then
        self::assertFalse($verified);
    }
}
