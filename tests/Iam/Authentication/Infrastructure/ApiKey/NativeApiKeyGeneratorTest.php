<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Infrastructure\ApiKey;

use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use Iam\Authentication\Infrastructure\ApiKey\NativeApiKeyGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NativeApiKeyGeneratorTest extends TestCase
{
    private NativeApiKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new NativeApiKeyGenerator();
    }

    #[Test]
    public function itGenerates(): void
    {
        // When
        $a = $this->generator->generate();
        $b = $this->generator->generate();

        // Then
        KeyId::fromString($a->keyId);
        self::assertSame(64, \strlen($a->secret));
        self::assertNotSame($a->keyId, $b->keyId);
        self::assertNotSame($a->secret, $b->secret);
    }
}
