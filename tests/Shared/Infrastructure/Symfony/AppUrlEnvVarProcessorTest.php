<?php

declare(strict_types=1);

namespace Shared\Tests\Infrastructure\Symfony;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Symfony\AppUrlEnvVarProcessor;

final class AppUrlEnvVarProcessorTest extends TestCase
{
    #[Test]
    public function itPrefixesWhenInProduction(): void
    {
        // When
        $url = new AppUrlEnvVarProcessor('test-app')->getEnv('app_url', 'APP_URL', $this->env('prod'));

        // Then
        self::assertSame('https://test-app.example.com', $url);
    }

    #[Test]
    public function itPassesThroughOutsideProduction(): void
    {
        // When
        $url = new AppUrlEnvVarProcessor('test-app')->getEnv('app_url', 'APP_URL', $this->env('dev'));

        // Then
        self::assertSame('https://example.com', $url);
    }

    #[Test]
    public function itFailsWhenNoApplicationInProduction(): void
    {
        // Then
        $this->expectException(\LogicException::class);

        // When
        new AppUrlEnvVarProcessor(null)->getEnv('app_url', 'APP_URL', $this->env('prod'));
    }

    #[Test]
    public function itProvidesTypes(): void
    {
        // When
        $types = AppUrlEnvVarProcessor::getProvidedTypes();

        // Then
        self::assertSame(['app_url' => 'string'], $types);
    }

    private function env(string $environment): \Closure
    {
        return static fn (string $name): string => match ($name) {
            'APP_ENV' => $environment,
            default => 'https://example.com',
        };
    }
}
