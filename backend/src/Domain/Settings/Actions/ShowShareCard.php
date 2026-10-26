<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\PublicPageData;
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
        $image = self::imageOf($public, $page);

        return new ShareCardData(
            title: $title,
            description: Str::limit(trim($text) !== '' ? trim($text) : __('share.default_description', ['name' => $name]), self::DESCRIPTION_MAX),
            // A JPEG copy (some apps ignore WebP in previews), served by the API next to this page.
            image_url: $image ? "{$base}/api/og/{$slug}".($isPortfolio ? '/portfolio' : '').'/imagem' : null,
            url: $base.'/'.$slug.($isPortfolio ? '/portfolio' : ''),
        );
    }

    /** The photo of the shared page: the portfolio cover (or the avatar), or the avatar. */
    public static function imageOf(PublicPageData $public, SharedPage $page): ?string
    {
        return $page === SharedPage::Portfolio ? ($public->portfolio->cover_url ?? $public->profile->avatar_url) : $public->profile->avatar_url;
    }
}
