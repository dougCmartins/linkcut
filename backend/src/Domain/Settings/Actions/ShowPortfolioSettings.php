<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\HighlightData;
use Domain\Settings\Data\ImageData;
use Domain\Settings\Data\PortfolioPhotoData;
use Domain\Settings\Data\PortfolioSettingsData;
use Domain\Settings\Models\PortfolioHighlight;
use Domain\Settings\Models\PortfolioPhoto;
use Domain\Settings\Models\PortfolioSettings;
use Spatie\LaravelData\DataCollection;

final class ShowPortfolioSettings
{
    public function handle(): PortfolioSettingsData
    {
        $portfolio = PortfolioSettings::query()->with('cover')->firstOrFail();
        $highlights = PortfolioHighlight::query()->orderBy('position')->get()
            ->map(fn (PortfolioHighlight $highlight): HighlightData => new HighlightData(label: $highlight->label, value: $highlight->value))
            ->all();
        $photos = PortfolioPhoto::query()->with('image')->orderBy('position')->get()
            ->map(fn (PortfolioPhoto $photo): PortfolioPhotoData => new PortfolioPhotoData(
                image_id: $photo->image_id,
                url: $photo->image->url(),
                alt: $photo->alt,
            ))
            ->all();

        return new PortfolioSettingsData(
            cover: $portfolio->cover ? new ImageData(id: $portfolio->cover->id, url: $portfolio->cover->url()) : null,
            quote: $portfolio->quote,
            story: $portfolio->story,
            highlights: new DataCollection(HighlightData::class, $highlights),
            photos: new DataCollection(PortfolioPhotoData::class, $photos),
            show_rating: $portfolio->show_rating,
            show_reviews: $portfolio->show_reviews,
        );
    }
}
