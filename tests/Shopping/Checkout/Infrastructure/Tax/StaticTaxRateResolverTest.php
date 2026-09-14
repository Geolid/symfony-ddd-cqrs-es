<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Tax;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\CountryCode;
use Shopping\Checkout\Infrastructure\Tax\StaticTaxRateResolver;

final class StaticTaxRateResolverTest extends TestCase
{
    #[Test]
    #[DataProvider('provideConfiguredCountries')]
    public function itResolves(CountryCode $country, int $expectedBasisPoints): void
    {
        // Given
        $resolver = new StaticTaxRateResolver();

        // When
        $taxRate = $resolver->resolve($country);

        // Then
        self::assertSame($expectedBasisPoints, $taxRate->basisPoints);
    }

    /**
     * @return iterable<string, array{CountryCode, int}>
     */
    public static function provideConfiguredCountries(): iterable
    {
        yield 'France' => [CountryCode::FR, 2_000];
        yield 'Germany' => [CountryCode::DE, 1_900];
        yield 'Italy' => [CountryCode::IT, 2_200];
    }

    #[Test]
    public function itThrowsWhenCountryNotConfigured(): void
    {
        // Given
        $resolver = new StaticTaxRateResolver();

        // Then
        $this->expectException(\InvalidArgumentException::class);

        // When
        $resolver->resolve(CountryCode::ZZ);
    }
}
