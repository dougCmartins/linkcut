<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\ReviewsDemoSeeder;
use Domain\Review\Models\Review;
use Domain\Schedule\Models\BookingRequest;
use Domain\Settings\Actions\CreateDefaultSettings;
use Domain\Settings\Data\DefaultSettingsData;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Tenant\Context\CurrentTenant;
use Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The demo reviews are inserted through the models and a second run does not duplicate them. */
final class ReviewsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_inserts_five_fake_reviews_once(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'rafael']);
        app(CurrentTenant::class)->run($tenant->id, fn () => app(CreateDefaultSettings::class)->handle(new DefaultSettingsData));

        // Act
        app(ReviewsDemoSeeder::class)->forTenant($tenant);
        app(ReviewsDemoSeeder::class)->forTenant($tenant);

        // Assert
        $reviews = Review::withoutTenantScope()->where('tenant_id', $tenant->id)->get();
        $this->assertCount(5, $reviews);
        $this->assertCount(5, BookingRequest::withoutTenantScope()->where('tenant_id', $tenant->id)->get());
        $this->assertSame(3, $reviews->whereNotNull('featured_position')->count());
        $this->assertSame(1, $reviews->whereNull('comment')->count());
        $this->assertTrue($reviews->every(fn (Review $review): bool => $review->rating >= 1 && $review->rating <= 5));
        $settings = PortfolioSettings::withoutTenantScope()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertTrue($settings->show_rating);
        $this->assertTrue($settings->show_reviews);
    }
}
