<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\GetPasswordCredentialByLogin;

use Iam\Identity\Application\Exception\PasswordCredentialResultNotFoundException;
use Iam\Identity\Application\Query\GetPasswordCredentialByLogin\GetPasswordCredentialByLogin;
use Iam\Tests\Identity\Support\Doubles\FakeSecretHasher;
use Iam\Tests\Identity\Support\Doubles\StubPasswordPolicy;
use Iam\Tests\Identity\Support\Factory\PasswordCredentialTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetPasswordCredentialByLoginHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByLogin(): void
    {
        // Given
        $credential = PasswordCredentialTestFactory::new()
            ->withLogin('operator')
            ->withHasher(new FakeSecretHasher())
            ->withPolicy(new StubPasswordPolicy())
            ->store();

        // When
        $result = $this->ask(new GetPasswordCredentialByLogin('operator'));

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame('operator', $result->login);
    }

    #[Test]
    public function itFailsWhenTheLoginIsUnknown(): void
    {
        // Then
        $this->expectException(PasswordCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetPasswordCredentialByLogin('unknown'));
    }
}
