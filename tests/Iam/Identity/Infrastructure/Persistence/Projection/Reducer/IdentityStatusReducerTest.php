<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Infrastructure\Persistence\Projection\Reducer;

use Iam\Identity\Application\Enum\IdentityStatus;
use Iam\Identity\Infrastructure\Persistence\Projection\Reducer\IdentityStatusReducer;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class IdentityStatusReducerTest extends AbstractIntegrationTestCase
{
    private IdentityStatusReducer $reducer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reducer = $this->service(IdentityStatusReducer::class);
    }

    #[Test]
    public function itReducesToActiveWhenNeverSuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();

        // When
        $status = $this->reducer->statusFor($identity->id()->toString());

        // Then
        self::assertSame(IdentityStatus::ACTIVE, $status);
    }

    #[Test]
    public function itReducesToSuspendedAfterIdentitySuspended(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();

        // When
        $status = $this->reducer->statusFor($identity->id()->toString());

        // Then
        self::assertSame(IdentityStatus::SUSPENDED, $status);
    }

    #[Test]
    public function itReducesToActiveAfterIdentityReactivated(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();
        $identity->reactivate(new \DateTimeImmutable('now +00:00'));
        $this->store($identity);

        // When
        $status = $this->reducer->statusFor($identity->id()->toString());

        // Then
        self::assertSame(IdentityStatus::ACTIVE, $status);
    }
}
