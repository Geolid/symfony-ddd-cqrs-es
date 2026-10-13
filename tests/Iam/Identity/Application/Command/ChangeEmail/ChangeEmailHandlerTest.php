<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Application\Command\ChangeEmail;

use Iam\Identity\Application\Command\ChangeEmail\ChangeEmail;
use Iam\Identity\Application\Command\ChangeEmail\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\InvalidEmailChangeCodeException;
use Iam\Identity\Domain\ValueObject\IdentityVerificationCodePurpose;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use Iam\Tests\Identity\Support\Factory\IdentityIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\VerificationCodeKey;
use Shared\Infrastructure\VerificationCode\NativeCodeChallenger;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ChangeEmailHandlerTest extends AbstractIntegrationTestCase
{
    private IdentityFinderInterface $identityFinder;

    private NativeCodeChallenger $codeChallenger;

    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->identityFinder = $this->service(IdentityFinderInterface::class);
        $this->codeChallenger = $this->service(NativeCodeChallenger::class);
        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itChanges(): void
    {
        // Given
        $factory = IdentityFactory::new();
        $identity = $factory->create();
        $this->store($identity);
        $oldEmail = $identity->email->value;
        $this->uniqueness->claim(
            UniqueKey::for(IdentityUniqueKey::EMAIL),
            $oldEmail,
            $identity->id->toString(),
        );
        $newEmail = IdentityFactory::sample('email')->value;
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $identity->id->toString()), Clock::get()->now());

        // When
        $this->dispatch(new ChangeEmail($identity->id->toString(), $newEmail, $code));

        // Then
        $result = $this->identityFinder->ofId($identity->id->toString());
        self::assertSame($newEmail, $result->email);

        self::assertFalse($this->uniqueness->isClaimed(UniqueKey::for(IdentityUniqueKey::EMAIL), $oldEmail));
    }

    #[Test]
    public function itFailsWhenEmailAlreadyInUse(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $identity->id->toString()), Clock::get()->now());

        $email = IdentityFactory::sample('email')->value;
        $this->uniqueness->claim(
            UniqueKey::for(IdentityUniqueKey::EMAIL),
            $email,
            IdentityIdFactory::new()->create()->toString(),
        );

        // Then
        $this->expectException(IdentityEmailAlreadyInUseException::class);

        // When
        $this->dispatch(new ChangeEmail($identity->id->toString(), $email, $code));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = IdentityIdFactory::new()->create()->toString();
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $id), Clock::get()->now());

        // Then
        $this->expectException(IdentityNotFoundException::class);

        // When
        $this->dispatch(new ChangeEmail($id, IdentityFactory::sample('email')->value, $code));
    }

    #[Test]
    public function itFailsWhenErased(): void
    {
        // Given
        $identity = IdentityFactory::new()->erasureRequested()->erased()->create();
        $this->store($identity);
        $code = $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(IdentityAlreadyErasedException::class);

        // When
        $this->dispatch(new ChangeEmail($identity->id->toString(), IdentityFactory::sample('email')->value, $code));
    }

    #[Test]
    public function itFailsWhenCodeInvalid(): void
    {
        // Given
        $identity = IdentityFactory::new()->create();
        $this->store($identity);
        $this->codeChallenger->issue(VerificationCodeKey::for(IdentityVerificationCodePurpose::EMAIL_CHANGE, $identity->id->toString()), Clock::get()->now());

        // Then
        $this->expectException(InvalidEmailChangeCodeException::class);

        // When
        $this->dispatch(new ChangeEmail($identity->id->toString(), IdentityFactory::sample('email')->value, '000000'));
    }
}
