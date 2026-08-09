<?php

declare(strict_types=1);

namespace Cli\Tests\Console;

use Cli\Tests\Support\AbstractCliTestCase;
use Iam\Access\Application\Finder\Grant\GrantFinderInterface;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Command\Command;

final class RegisterIdentityCommandTest extends AbstractCliTestCase
{
    use ClockSensitiveTrait;

    #[Test]
    public function itRegistersAnIdentityWithAnApiKeyAndGrants(): void
    {
        // Given
        $now = new \DateTimeImmutable('2026-08-07T10:00:00+00:00');
        self::mockTime($now);
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register', '--label' => 'CI pipeline', '--permission' => ['fixture.widget:read', 'fixture.widget:write']]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        if (1 !== preg_match('/API key \(shown once, store it securely\): (\S+)\.(\S+)/', $tester->getDisplay(), $matches)) {
            self::fail('No API key found in the command output.');
        }

        $credential = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($matches[1]);
        self::assertNotNull($credential);
        self::assertNotEmpty($credential->identityId);
        self::assertSame('CI pipeline', $credential->label);
        self::assertSame($now->modify('+365 days')->format(\DateTimeInterface::ATOM), $credential->expiresAt->format(\DateTimeInterface::ATOM));

        $grants = array_values(iterator_to_array($this->service(GrantFinderInterface::class)->withIdentity($credential->identityId)));
        self::assertCount(2, $grants);
        self::assertEqualsCanonicalizing(['fixture.widget:read', 'fixture.widget:write'], array_map(static fn ($grant): string => $grant->permission, $grants));
    }

    #[Test]
    public function itFailsWhenNoLabelIsProvided(): void
    {
        // Given
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register', '--permission' => ['fixture.widget:read']]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('label:', $tester->getDisplay());
    }

    #[Test]
    public function itFailsWhenNoPermissionIsProvided(): void
    {
        // Given
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register', '--label' => 'CI pipeline']);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('--permission:', $tester->getDisplay());
    }

    #[Test]
    public function itFailsWhenAPermissionIsMalformed(): void
    {
        // Given
        $tester = $this->tester();

        // When
        $tester->run(['command' => 'iam:identity:register', '--label' => 'CI pipeline', '--permission' => ['not-a-valid-permission-format']]);

        // Then
        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('permission[0]:', $tester->getDisplay());
    }
}
