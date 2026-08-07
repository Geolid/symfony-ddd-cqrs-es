<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;

final class RegisterIdentityCommandTest extends AbstractCliTestCase
{
    #[Test]
    public function itRegistersAnIdentityWithAnApiKeyAndGrants(): void
    {
        // When
        $tester = $this->tester('iam:identity:register');
        $tester->execute(['--permission' => ['sales:read', 'fulfilment:write']]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        if (1 !== preg_match('/API key \(shown once, store it securely\): (\S+)\.(\S+)/', $tester->getDisplay(), $matches)) {
            self::fail('No API key found in the command output.');
        }

        $credential = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($matches[1]);
        self::assertNotNull($credential);
        self::assertNotEmpty($credential->identityId);
        self::assertGreaterThan(new \DateTimeImmutable('now +00:00')->modify('+364 days'), $credential->expiresAt);

        $grants = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity($credential->identityId)));
        self::assertCount(2, $grants);
        self::assertSame(['sales:read', 'fulfilment:write'], array_map(static fn ($grant): string => $grant->permission, $grants));
    }

    #[Test]
    public function itFailsWhenNoPermissionIsProvided(): void
    {
        // When
        $tester = $this->tester('iam:identity:register');
        $tester->execute([]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    #[Test]
    public function itFailsWhenAPermissionIsMalformed(): void
    {
        // When
        $tester = $this->tester('iam:identity:register');
        $tester->execute(['--permission' => ['not-a-valid-permission-format']]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }
}
