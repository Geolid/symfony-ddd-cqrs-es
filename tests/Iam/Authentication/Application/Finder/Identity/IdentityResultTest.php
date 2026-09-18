<?php

declare(strict_types=1);

namespace Iam\Tests\Authentication\Application\Finder\Identity;

use Iam\Authentication\Application\Finder\Identity\IdentityResult;
use Iam\Authentication\Application\IdentityModerationStatus;
use Iam\Authentication\Application\IdentityVerificationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class IdentityResultTest extends TestCase
{
    #[Test]
    #[DataProvider('provideStatuses')]
    public function itIsAuthenticatable(
        IdentityVerificationStatus $verificationStatus,
        IdentityModerationStatus $moderationStatus,
        bool $expected,
    ): void {
        $result = new IdentityResult(
            identityId: Uuid::uuid7()->toString(),
            fullName: 'John Doe',
            email: 'john.doe@example.com',
            verificationStatus: $verificationStatus,
            moderationStatus: $moderationStatus,
        );

        self::assertSame($expected, $result->isAuthenticatable());
    }

    /**
     * @return iterable<string, array{IdentityVerificationStatus, IdentityModerationStatus, bool}>
     */
    public static function provideStatuses(): iterable
    {
        yield 'confirmed and active' => [IdentityVerificationStatus::CONFIRMED, IdentityModerationStatus::ACTIVE, true];
        yield 'confirmed and suspended' => [IdentityVerificationStatus::CONFIRMED, IdentityModerationStatus::SUSPENDED, false];
        yield 'pending and active' => [IdentityVerificationStatus::PENDING, IdentityModerationStatus::ACTIVE, false];
        yield 'pending and suspended' => [IdentityVerificationStatus::PENDING, IdentityModerationStatus::SUSPENDED, false];
    }
}
