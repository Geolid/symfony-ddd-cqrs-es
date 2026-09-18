<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ConfirmIdentityEmail;

use Iam\Identity\Application\Command\ConfirmIdentityEmail\ConfirmIdentityEmail;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityStatus;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\VerificationCode\VerificationCode;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ConfirmIdentityEmailHandlerTest extends AbstractIntegrationTestCase
{
    private VerificationCode $verificationCode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verificationCode = $this->service(VerificationCode::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $code = $this->verificationCode->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString(), Clock::get()->now());

        // When
        $this->dispatch(new ConfirmIdentityEmail($identity->id->toString(), $code));

        // Then
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());
        self::assertSame(IdentityStatus::ACTIVE, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyActive(): void
    {
        // Given
        $identity = IdentityBuilder::new()->activated()->create();
        $this->store($identity);
        $code = $this->verificationCode->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString(), Clock::get()->now());

        // When
        $this->dispatch(new ConfirmIdentityEmail($identity->id->toString(), $code));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ConfirmIdentityEmail(Uuid::uuid7()->toString(), '123456'));
    }

    #[Test]
    public function itFailsWhenCodeInvalid(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $this->verificationCode->issue(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $identity->id->toString(), Clock::get()->now());

        // Then
        $this->expectException(InvalidConfirmationCodeException::class);

        // When
        $this->dispatch(new ConfirmIdentityEmail($identity->id->toString(), 'wrong'));
    }
}
