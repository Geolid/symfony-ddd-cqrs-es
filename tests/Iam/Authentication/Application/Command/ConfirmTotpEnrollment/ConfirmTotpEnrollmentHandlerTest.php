<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Command\ConfirmTotpEnrollment;

use Iam\Authentication\Application\Command\ConfirmTotpEnrollment\ConfirmTotpEnrollment;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Tests\Authentication\Support\Builder\TotpCredentialBuilder;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmTotpEnrollmentHandlerTest extends AbstractIntegrationTestCase
{
    private TotpCredentialFinderInterface $finder;
    private TotpCipherInterface $cipher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(TotpCredentialFinderInterface::class);
        $this->cipher = $this->service(TotpCipherInterface::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $secret = TOTP::generate()->getSecret();
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher)->withSecret($secret);
        $credential = $builder->create();
        $this->store($credential);

        $code = TOTP::createFromSecret($secret, Clock::get())->now();

        // When
        $this->dispatch(new ConfirmTotpEnrollment($credential->id->toString(), $builder['identityId'], $code));

        // Then
        $result = $this->finder->ofId($credential->id->toString());
        self::assertTrue($result->confirmed);
        self::assertNotNull($result->confirmedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(TotpCredentialNotFoundException::class);

        // When
        $this->dispatch(new ConfirmTotpEnrollment(
            Uuid::uuid7()->toString(),
            TotpCredentialBuilder::sample('identityId'),
            '000000',
        ));
    }

    #[Test]
    public function itFailsWhenOwnedByAnotherIdentity(): void
    {
        // Given
        $credential = TotpCredentialBuilder::new()->withCipher($this->cipher)->create();
        $this->store($credential);

        // Then
        $this->expectException(TotpCredentialOwnedByAnotherIdentityException::class);

        // When
        $this->dispatch(new ConfirmTotpEnrollment(
            $credential->id->toString(),
            TotpCredentialBuilder::sample('identityId'),
            '000000',
        ));
    }

    #[Test]
    public function itFailsWithInvalidCode(): void
    {
        // Given
        $builder = TotpCredentialBuilder::new()->withCipher($this->cipher);
        $credential = $builder->create();
        $this->store($credential);

        // Then
        $this->expectException(InvalidTotpCodeException::class);

        // When
        $this->dispatch(new ConfirmTotpEnrollment($credential->id->toString(), $builder['identityId'], '000000'));
    }
}
