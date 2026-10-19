<?php

declare(strict_types=1);

namespace Domain\Settings\Actions;

use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Data\ProfileSettingsData;
use Domain\Settings\Data\SocialLinkData;
use Domain\Settings\Data\UpdateProfileSettingsData;
use Domain\Settings\Exceptions\ImageNotFoundException;
use Domain\Settings\Models\Image;
use Domain\Settings\Models\ProfileSettings;
use Domain\Settings\Models\SocialLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/** Saves the "Link" tab: profile texts, avatar/background and social links. */
final class UpdateProfileSettings
{
    public function __construct(
        private readonly CreateDefaultSettings $createDefaultSettings,
        private readonly ShowProfileSettings $showProfile,
    ) {}

    public function handle(UpdateProfileSettingsData $data): ProfileSettingsData
    {
        $this->ensureValid($data);
        $this->createDefaultSettings->handle(new DefaultSettingsData);

        DB::transaction(function () use ($data): void {
            ProfileSettings::query()->firstOrFail()->update([
                'name' => $data->name,
                'handle' => $data->handle ?? '',
                'bio' => $data->bio ?? '',
                'avatar_image_id' => $data->avatar_image_id,
                'background_image_id' => $data->background_image_id,
                'whatsapp_number' => preg_replace('/\D/', '', $data->whatsapp_number),
                'whatsapp_message' => $data->whatsapp_message ?? '',
            ]);

            SocialLink::query()->delete();
            foreach ($data->socials as $position => $social) {
                /** @var SocialLinkData $social */
                SocialLink::query()->create(['network' => $social->network, 'url' => $social->url, 'position' => $position]);
            }
        });

        Log::info('settings_profile_updated');

        return $this->showProfile->handle();
    }

    private function ensureValid(UpdateProfileSettingsData $data): void
    {
        $networks = array_map(fn (SocialLinkData $social): string => $social->network->value, $data->socials->all());
        foreach (array_diff_assoc($networks, array_unique($networks)) as $index => $network) {
            throw ValidationException::withMessages(["socials.{$index}.network" => 'distinct']);
        }

        if (Image::missingIds([(int) $data->avatar_image_id, (int) $data->background_image_id])) {
            throw new ImageNotFoundException;
        }
    }
}
