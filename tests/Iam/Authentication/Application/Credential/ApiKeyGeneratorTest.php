<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Credential;

use Iam\Authentication\Application\Credential\ApiKeyGenerator;
use Iam\Authentication\Domain\ApiKeyCredential\ValueObject\KeyId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiKeyGeneratorTest extends TestCase
{
    private ApiKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new ApiKeyGenerator();
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
