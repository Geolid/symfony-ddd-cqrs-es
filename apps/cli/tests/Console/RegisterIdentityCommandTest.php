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
        $tester->execute(['--permission' => ['sales:supervise', 'fulfilment:supervise']]);

        // Then
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        preg_match('/API key \(shown once, store it securely\): (\S+)\.(\S+)/', $tester->getDisplay(), $matches);
        [, $identifier] = $matches;

        $credential = $this->service(ApiTokenCredentialFinderInterface::class)->ofIdentifier($identifier);
        self::assertNotNull($credential);

        $grants = iterator_to_array($this->service(GrantFinderInterface::class)->forIdentity($credential->identityId));
        self::assertCount(2, $grants);
    }
}
