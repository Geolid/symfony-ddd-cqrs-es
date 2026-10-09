<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Spike;

use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\IdentityVerificationStatus;
use Iam\Tests\Identity\Support\Factory\IdentityFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\Foundry\CustomerFactory;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * SPIKE — throwaway. Same scenario as DbalIdentityFinderTest::itGetsById, built through Foundry.
 */
final class FoundryKernelTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsFoundryBuiltIdentity(): void
    {
        // Given
        $other = IdentityFactory::new()->create();
        $identity = IdentityFactory::new()->confirmed()->create();
        $this->store($other, $identity);

        // When
        $result = $this->service(IdentityFinderInterface::class)->ofId($identity->id->toString());

        // Then
        $inputs = IdentityFactory::inputs($identity);
        self::assertSame($identity->id->toString(), $result->id);
        self::assertSame($inputs['fullName']->value, $result->fullName);
        self::assertSame($inputs['email']->value, $result->email);
        self::assertSame(IdentityVerificationStatus::CONFIRMED, $result->verificationStatus);
        self::assertSame(
            $inputs['registeredAt']->format(\DateTimeInterface::ATOM),
            $result->registeredAt->format(\DateTimeInterface::ATOM),
        );
    }

    #[Test]
    public function itUsesTheCustomFakerInKernelMode(): void
    {
        $customers = CustomerFactory::new()->many(50)->create();

        self::assertCount(50, $customers);
    }
}
