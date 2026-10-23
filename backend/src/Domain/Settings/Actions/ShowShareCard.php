<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\ShareCardData;
use Domain\Settings\Data\ShareCardQueryData;
use Domain\Settings\Enums\SharedPage;
use Illuminate\Support\Str;

/**
 * The preview of the current barbershop's public page (or portfolio) for link-preview crawlers, which do not run
 * the SPA's JavaScript. Texts are plain strings: the view escapes them.
 */
final class ShowShareCard
{
    private const DESCRIPTION_MAX = 160;

    public function __construct(private readonly ShowPublicPage $showPublicPage) {}

    public function handle(ShareCardQueryData $data): ShareCardData
    {
        $public = $this->showPublicPage->handle();
        $page = $data->page;
        $slug = $data->slug;
        $base = rtrim((string) config('app.frontend_url'), '/');
        $name = $public->profile->name;

        $isPortfolio = $page === SharedPage::Portfolio;
        $title = $isPortfolio ? __('share.portfolio_title', ['name' => $name]) : $name;
        $text = $isPortfolio ? ($public->portfolio->quote ?: $public->portfolio->story) : $public->profile->bio;
        $image = $isPortfolio ? ($public->portfolio->cover_url ?? $public->profile->avatar_url) : $public->profile->avatar_url;

        return new ShareCardData(
            title: $title,
            description: Str::limit(trim($text) !== '' ? trim($text) : __('share.default_description', ['name' => $name]), self::DESCRIPTION_MAX),
            image_url: $image ? self::absolute($base, $image) : null,
            url: $base.'/'.$slug.($isPortfolio ? '/portfolio' : ''),
        );
    }

    /** "/storage/x.webp" → "https://domain/storage/x.webp"; already absolute (CDN) stays as is. */
    private static function absolute(string $base, string $url): string
    {
        return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
    }
}
