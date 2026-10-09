<?php

declare(strict_types=1);

namespace Storefront\Tests\Feature\Home;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Storefront\Tests\Support\AbstractStorefrontTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Request;

final class ShowTest extends AbstractStorefrontTestCase
{
    #[Test]
    #[DataProvider('provideLocalizedPath')]
    public function itShows(string $locale, string $path): void
    {
        // Given
        $browser = $this->activeBrowser();

        // When
        $browser->visit($path);

        // Then
        $browser->assertSuccessful()
            ->assertSeeElement('[data-testid="home-title"]')
            ->use(static function (AbstractBrowser $client) use ($locale): void {
                $request = $client->getRequest();
                \assert($request instanceof Request);
                self::assertSame($locale, $request->getLocale());
            });
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLocalizedPath(): iterable
    {
        yield 'en' => ['en', '/home'];
        yield 'fr' => ['fr', '/accueil'];
    }

    /**
     * @param array<string, string> $acceptLanguage
     */
    #[Test]
    #[DataProvider('providePreferredLocale')]
    public function itRedirectsToPreferredLocale(array $acceptLanguage, string $expectedPath): void
    {
        // Given
        $browser = $this->browser()->interceptRedirects();

        // When
        $browser->get($this->path('storefront_home_root'), ['headers' => $acceptLanguage]);

        // Then
        $browser->assertRedirectedTo($expectedPath);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function providePreferredLocale(): iterable
    {
        yield 'default' => [[], '/home'];
        yield 'fr' => [['Accept-Language' => 'fr'], '/accueil'];
    }
}
