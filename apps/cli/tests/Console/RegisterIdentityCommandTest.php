<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class RegisterIdentityCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itRegistersIdentityWithApiKey(): void
    {
        // Given
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register', '--label' => 'CI pipeline']);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        if (1 !== preg_match('/API key \(shown once, store it securely\): (\S+)\.(\S+)/', $tester->getDisplay(), $matches)) {
            self::fail('No API key found in the command output.');
        }

        $credential = $this->service(ApiKeyCredentialFinderInterface::class)->ofKeyId($matches[1]);
        self::assertNotEmpty($credential->identityId);
        self::assertSame('CI pipeline', $credential->label);
    }

    #[Test]
    public function itFailsToRegisterWithoutLabel(): void
    {
        // Given
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register']);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('label:', $tester->getDisplay());
    }
}
