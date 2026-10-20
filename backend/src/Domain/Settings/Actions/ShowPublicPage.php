<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\HighlightData;
use Domain\Settings\Data\PortfolioPhotoData;
use Domain\Settings\Data\PublicPageData;
use Domain\Settings\Data\PublicPhotoData;
use Domain\Settings\Data\PublicPortfolioData;
use Domain\Settings\Data\PublicProfileData;
use Domain\Settings\Data\SocialLinkData;
use Spatie\LaravelData\DataCollection;

/** Bio link + portfolio + theme of the barbershop resolved by slug, as visitors see them. */
final class ShowPublicPage
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowProfileSettings $showProfile,
        private readonly ShowPortfolioSettings $showPortfolio,
        private readonly ShowThemeSettings $showTheme,
    ) {}

    public function handle(): PublicPageData
    {
        $this->createDefaultSettings->handle(new DefaultSettingsData);
        $profile = $this->showProfile->handle();
        $portfolio = $this->showPortfolio->handle();

        $socials = array_values(array_filter($profile->socials->all(), fn (SocialLinkData $social): bool => (bool) $social->url));
        $highlights = array_values(array_filter($portfolio->highlights->all(), fn (HighlightData $highlight): bool => (bool) $highlight->value));
        $photos = array_map(
            fn (PortfolioPhotoData $photo): PublicPhotoData => new PublicPhotoData(url: $photo->url, alt: $photo->alt),
            $portfolio->photos->all(),
        );

        return new PublicPageData(
            profile: new PublicProfileData(
                name: $profile->name,
                handle: $profile->handle,
                bio: $profile->bio,
                avatar_url: $profile->avatar?->url,
                background_url: $profile->background?->url,
                whatsapp_number: $profile->whatsapp_number,
                whatsapp_message: $profile->whatsapp_message,
                socials: new DataCollection(SocialLinkData::class, $socials),
                address: $profile->address,
            ),
            portfolio: new PublicPortfolioData(
                cover_url: $portfolio->cover?->url,
                quote: $portfolio->quote,
                story: $portfolio->story,
                highlights: new DataCollection(HighlightData::class, $highlights),
                photos: new DataCollection(PublicPhotoData::class, $photos),
            ),
            theme: $this->showTheme->handle(),
        );
    }
}
