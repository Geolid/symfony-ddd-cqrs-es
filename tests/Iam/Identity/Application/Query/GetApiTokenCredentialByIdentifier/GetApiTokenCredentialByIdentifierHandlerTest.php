<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Query\GetApiTokenCredentialByIdentifier;

use Iam\Identity\Application\Exception\ApiTokenCredentialResultNotFoundException;
use Iam\Identity\Application\Query\GetApiTokenCredentialByIdentifier\GetApiTokenCredentialByIdentifier;
use Iam\Tests\Identity\Support\Factory\ApiTokenCredentialTestFactory;
use Iam\Tests\Identity\Support\Stub\DummySecretHasher;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetApiTokenCredentialByIdentifierHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsByIdentifier(): void
    {
        // Given
        $credential = ApiTokenCredentialTestFactory::new()
            ->withIdentifier('key_operator')
            ->withLabel('Operator key')
            ->withHasher(new DummySecretHasher())
            ->store();

        // When
        $result = $this->ask(new GetApiTokenCredentialByIdentifier('key_operator'));

        // Then
        self::assertSame($credential->id()->toString(), $result->id);
        self::assertSame('Operator key', $result->label);
    }

    #[Test]
    public function itFailsWhenTheIdentifierIsUnknown(): void
    {
        // Then
        $this->expectException(ApiTokenCredentialResultNotFoundException::class);

        // When
        $this->ask(new GetApiTokenCredentialByIdentifier('key_unknown'));
    }
}
