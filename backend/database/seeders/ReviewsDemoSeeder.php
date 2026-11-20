<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Domain\Client\Models\Client;
use Domain\Identity\Models\User;
use Domain\Review\Models\Review;
use Domain\Review\Support\AuthorName;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Models\BookingRequest;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Models\Service;
use Domain\Settings\Support\PublicSettingsCache;
use Domain\Shared\Support\PlainText;
use Domain\Team\Actions\CreateOwnerStaff;
use Domain\Team\Data\NewOwnerStaffData;
use Domain\Team\Models\Staff;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use RuntimeException;

/** Five fake reviews for the demo barbershop, only when it has none. Three comments are featured. */
final class ReviewsDemoSeeder extends Seeder
{
    private const COUNT = 5;

    private const FEATURED = 3;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The demo seeder does not run in production.');
        }

        $tenant = Tenant::query()->where('slug', 'rafael')->first();
        if ($tenant === null) {
            return;
        }

        $this->forTenant($tenant);
    }

    public function forTenant(Tenant $tenant): void
    {
        app(CurrentTenant::class)->run($tenant->id, function () use ($tenant): void {
            if (Review::query()->exists()) {
                return;
            }

            $staffId = $this->ensureStaffId($tenant);
            $service = Service::query()->ordered()->firstOrFail();
            $faker = fake('pt_BR');
            $featured = 0;

            for ($index = 0; $index < self::COUNT; $index++) {
                $name = PlainText::name($faker->name());
                $client = Client::factory()->create([
                    'name' => $name,
                    'phone' => $faker->numerify('119########'),
                ]);
                $withComment = $index < self::COUNT - 1;
                $comment = $withComment ? PlainText::text($faker->sentence(12)) : null;
                $when = CarbonImmutable::now()->subDays(self::COUNT - $index)->setTime(10 + $index, 0);
                $booking = BookingRequest::query()->create([
                    'staff_id' => $staffId,
                    'client_id' => $client->id,
                    'client_name' => $name,
                    'client_whatsapp' => $client->phone,
                    'client_email' => $client->email,
                    'service_id' => $service->id,
                    'service_ids' => [$service->id],
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price_in_cents' => $service->price_in_cents,
                    'date' => $when->toDateString(),
                    'starts_at' => $when->format('H:i'),
                    'ends_at' => $when->addMinutes($service->duration_minutes)->format('H:i'),
                    'status' => BookingStatus::Completed,
                    'completed_at' => $when,
                ]);

                $position = null;
                if ($comment !== null && $featured < self::FEATURED) {
                    $featured++;
                    $position = $featured;
                }

                Review::query()->create([
                    'client_id' => $client->id,
                    'booking_request_id' => $booking->id,
                    'rating' => $faker->numberBetween(4, 5),
                    'comment' => $comment,
                    'author_first_name' => AuthorName::first($name),
                    'published_at' => $when,
                    'featured_position' => $position,
                ]);
            }

            PortfolioSettings::query()->firstOrFail()->update([
                'show_rating' => true,
                'show_reviews' => true,
            ]);
            app(PublicSettingsCache::class)->forget();
        });
    }

    private function ensureStaffId(Tenant $tenant): int
    {
        $existing = Staff::query()->where('active', true)->orderBy('id')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $owner = User::query()->where('tenant_id', $tenant->id)->orderBy('id')->first()
            ?? User::factory()->create(['tenant_id' => $tenant->id]);

        return app(CreateOwnerStaff::class)->handle(new NewOwnerStaffData(
            tenant_id: $tenant->id,
            user_id: $owner->id,
            name: $owner->name,
        ))->id;
    }
}
