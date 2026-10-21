<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\HighlightData;
use Domain\Settings\Data\PortfolioPhotoInputData;
use Domain\Settings\Data\PortfolioSettingsData;
use Domain\Settings\Data\UpdatePortfolioSettingsData;
use Domain\Settings\Exceptions\ImageNotFoundException;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\PortfolioHighlight;
use Domain\Settings\Models\PortfolioPhoto;
use Domain\Settings\Models\PortfolioSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Saves the "Portfólio" tab: cover, texts, highlights and photos (in order). */
final class UpdatePortfolioSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowPortfolioSettings $showPortfolio,
    ) {}

    public function handle(UpdatePortfolioSettingsData $data): PortfolioSettingsData
    {
        $photoImageIds = array_map(fn (PortfolioPhotoInputData $photo): int => $photo->image_id, $data->photos->all());
        if (Image::missingIds([...$photoImageIds, (int) $data->cover_image_id])) {
            throw new ImageNotFoundException;
        }
        $this->createDefaultSettings->handle(new DefaultSettingsData);

        DB::transaction(function () use ($data): void {
            PortfolioSettings::query()->firstOrFail()->update([
                'cover_image_id' => $data->cover_image_id,
                'quote' => $data->quote ?? '',
                'story' => $data->story ?? '',
            ]);

            PortfolioHighlight::query()->delete();
            PortfolioHighlight::insertForCurrentTenant(array_map(
                fn (HighlightData $highlight, int $position): array => ['label' => $highlight->label ?? '', 'value' => $highlight->value ?? '', 'position' => $position],
                $data->highlights->all(),
                array_keys($data->highlights->all()),
            ));

            PortfolioPhoto::query()->delete();
            PortfolioPhoto::insertForCurrentTenant(array_map(
                fn (PortfolioPhotoInputData $photo, int $position): array => ['image_id' => $photo->image_id, 'alt' => $photo->alt, 'position' => $position],
                $data->photos->all(),
                array_keys($data->photos->all()),
            ));
        });

        Log::info('settings_portfolio_updated', ['photos' => $data->photos->count()]);

        return $this->showPortfolio->handle();
    }
}
