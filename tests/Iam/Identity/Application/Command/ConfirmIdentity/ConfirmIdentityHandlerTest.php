<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ConfirmIdentity;

use Iam\Identity\Application\Command\ConfirmIdentity\ConfirmIdentity;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmIdentityHandlerTest extends AbstractIntegrationTestCase
{
    private NativeCodeChallenger $codeChallenger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codeChallenger = $this->service(NativeCodeChallenger::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString()), Clock::get()->now());

        // When
        $this->dispatch(new ConfirmIdentity($identity->id->toString(), $code));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityVerificationStatus::CONFIRMED, $result->verificationStatus);
    }

    #[Test]
    public function itIgnoresWhenAlreadyConfirmed(): void
    {
        // Given
        $identity = IdentityBuilder::new()->confirmed()->create();
        $this->store($identity);
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString()), Clock::get()->now());

        // When
        $this->dispatch(new ConfirmIdentity($identity->id->toString(), $code));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ConfirmIdentity(Uuid::uuid7()->toString(), '123456'));
    }

    #[Test]
    public function itFailsWhenCodeInvalid(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(InvalidConfirmationCodeException::class);

        // When
        $this->dispatch(new ConfirmIdentity($identity->id->toString(), 'wrong'));
    }
}
